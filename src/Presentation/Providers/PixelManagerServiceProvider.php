<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Presentation\Providers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use MehdiyevSignal\PixelManager\Application\Services\ConfigService;
use MehdiyevSignal\PixelManager\Application\Services\EventDistributor;
use MehdiyevSignal\PixelManager\Application\Services\EventFactory;
use MehdiyevSignal\PixelManager\Application\Services\PlatformSelector;
use MehdiyevSignal\PixelManager\Application\UseCases\TrackPixelEvent\TrackPixelEventHandler;
use MehdiyevSignal\PixelManager\Domain\Repositories\CredentialsRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\Repositories\EventLogRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\Services\BotDetectorInterface;
use MehdiyevSignal\PixelManager\Domain\Services\CredentialsEncryptorInterface;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Decorators\CircuitBreakerPlatformAdapter;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Decorators\LoggingPlatformAdapter;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Decorators\RateLimitingPlatformAdapter;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Decorators\RetryingPlatformAdapter;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Factories\PlatformAdapterFactory;
use MehdiyevSignal\PixelManager\Infrastructure\Persistence\Cache\CachedCredentialsRepository;
use MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB\Mappings\CredentialsMapper;
use MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB\MongoDBCredentialsRepository;
use MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB\MongoDBEventLogRepository;
use MehdiyevSignal\PixelManager\Infrastructure\Security\BotDetector;
use MehdiyevSignal\PixelManager\Infrastructure\Security\LaravelCredentialsEncryptor;
use MehdiyevSignal\PixelManager\Presentation\Facades\PixelManagerFacadeImpl;
use Psr\Log\LoggerInterface;

/**
 * Pixel Manager Service Provider.
 *
 * Registers all services, bindings, and configurations for the package.
 */
class PixelManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../../../config/pixel-manager.php',
            'pixel-manager'
        );

        // Register ConfigService
        $this->app->singleton(ConfigService::class, function ($app) {
            return new ConfigService($app['config']['pixel-manager']);
        });

        // Register Domain Services
        $this->app->bind(BotDetectorInterface::class, BotDetector::class);

        $this->app->singleton(CredentialsEncryptorInterface::class, function ($app) {
            return new LaravelCredentialsEncryptor(
                $app->make(Encrypter::class),
                $app->make(LoggerInterface::class)
            );
        });

        // Register Repositories
        $this->registerRepositories();

        // Register Application Services
        $this->registerApplicationServices();

        // Register Platform Adapter Factory
        $this->registerPlatformAdapterFactory();

        // Register Facade
        $this->app->singleton('pixel-manager', function ($app) {
            return new PixelManagerFacadeImpl(
                $app->make(TrackPixelEventHandler::class),
                $app->make(ConfigService::class),
                $app->make(CredentialsRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../../../config/pixel-manager.php' => config_path('pixel-manager.php'),
        ], 'pixel-manager-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../../../database/migrations/' => database_path('migrations'),
        ], 'pixel-manager-migrations');
    }

    /**
     * Register repositories with caching decorator.
     */
    private function registerRepositories(): void
    {
        $this->app->singleton(CredentialsRepositoryInterface::class, function ($app) {
            $config = $app->make(ConfigService::class);
            $driver = config('pixel-manager.driver', 'mongodb');

            // Create base repository based on driver
            if ($driver === 'sql') {
                $baseRepo = new \MehdiyevSignal\PixelManager\Infrastructure\Persistence\SQL\SQLCredentialsRepository(
                    config('pixel-manager.sql.connection', 'mysql'),
                    config('pixel-manager.sql.credentials_table', 'pixel_manager_credentials'),
                    $app->make(CredentialsEncryptorInterface::class)
                );
            } else {
                // Default: MongoDB
                $baseRepo = new MongoDBCredentialsRepository(
                    $config->getDbConnection(),
                    $config->getApplicationsCollection(),
                    new CredentialsMapper(),
                    $app->make(CredentialsEncryptorInterface::class)
                );
            }

            // Wrap with caching decorator if enabled
            if ($config->isCachingEnabled()) {
                return new CachedCredentialsRepository(
                    $baseRepo,
                    $app->make(CacheRepository::class),
                    $config->getCacheTtl()
                );
            }

            return $baseRepo;
        });

        $this->app->singleton(EventLogRepositoryInterface::class, function ($app) {
            $config = $app->make(ConfigService::class);
            $driver = config('pixel-manager.driver', 'mongodb');

            // Create repository based on driver
            if ($driver === 'sql') {
                return new \MehdiyevSignal\PixelManager\Infrastructure\Persistence\SQL\SQLEventLogRepository(
                    config('pixel-manager.sql.connection', 'mysql'),
                    config('pixel-manager.sql.events_table', 'pixel_manager_events')
                );
            }

            // Default: MongoDB
            return new MongoDBEventLogRepository(
                $config->getDbConnection(),
                $config->getEventCollection()
            );
        });
    }

    /**
     * Register application services.
     */
    private function registerApplicationServices(): void
    {
        $this->app->singleton(EventFactory::class);
        $this->app->singleton(PlatformSelector::class);
        $this->app->singleton(EventDistributor::class);
        $this->app->singleton(TrackPixelEventHandler::class);
    }

    /**
     * Register platform adapter factory with decorators.
     */
    private function registerPlatformAdapterFactory(): void
    {
        $this->app->singleton(PlatformAdapterFactory::class, function ($app) {
            $factory = new PlatformAdapterFactory($app);
            $config = $app->make(ConfigService::class);

            // Add decorators based on configuration
            $factory->addDecorator(LoggingPlatformAdapter::class);

            if ($config->isRateLimitingEnabled()) {
                $factory->addDecorator(RateLimitingPlatformAdapter::class);
            }

            if ($config->isCircuitBreakerEnabled()) {
                $factory->addDecorator(CircuitBreakerPlatformAdapter::class);
            }

            if ($config->isRetryEnabled()) {
                $factory->addDecorator(RetryingPlatformAdapter::class);
            }

            return $factory;
        });

        // Bind HttpFactory
        $this->app->bind(HttpFactory::class, function () {
            return new HttpFactory();
        });
    }
}
