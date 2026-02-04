<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Factories;

use Illuminate\Contracts\Container\Container;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformAdapterInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\BrevoPlatformAdapter;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\GooglePlatformAdapter;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\MetaPlatformAdapter;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\PinterestPlatformAdapter;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\SnapchatPlatformAdapter;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\TikTokPlatformAdapter;

/**
 * Factory for creating platform adapters with decorators.
 *
 * Implements Factory pattern and applies decorators in correct order.
 */
final class PlatformAdapterFactory
{
    /**
     * @var array<string, string> Platform type => Adapter class
     */
    private array $adapters = [];

    /**
     * @var array<string> Decorator classes to apply
     */
    private array $decorators = [];

    public function __construct(
        private readonly Container $container
    ) {
        $this->registerDefaultAdapters();
    }

    /**
     * Register default platform adapters.
     */
    private function registerDefaultAdapters(): void
    {
        $this->adapters = [
            PlatformType::META->value => MetaPlatformAdapter::class,
            PlatformType::GOOGLE->value => GooglePlatformAdapter::class,
            PlatformType::TIKTOK->value => TikTokPlatformAdapter::class,
            PlatformType::PINTEREST->value => PinterestPlatformAdapter::class,
            PlatformType::SNAPCHAT->value => SnapchatPlatformAdapter::class,
            PlatformType::BREVO->value => BrevoPlatformAdapter::class,
        ];
    }

    /**
     * Register a custom platform adapter.
     *
     * @param PlatformType $platform
     * @param string $adapterClass
     */
    public function register(PlatformType $platform, string $adapterClass): void
    {
        $this->adapters[$platform->value] = $adapterClass;
    }

    /**
     * Add a decorator to be applied.
     *
     * @param string $decoratorClass
     */
    public function addDecorator(string $decoratorClass): void
    {
        $this->decorators[] = $decoratorClass;
    }

    /**
     * Create platform adapter with decorators.
     *
     * @param PlatformType $platform
     * @return PlatformAdapterInterface
     * @throws \RuntimeException
     */
    public function create(PlatformType $platform): PlatformAdapterInterface
    {
        if (!isset($this->adapters[$platform->value])) {
            throw new \RuntimeException("No adapter registered for platform: {$platform->value}");
        }

        // Create base adapter
        $adapter = $this->container->make($this->adapters[$platform->value]);

        // Apply decorators in order
        foreach ($this->decorators as $decoratorClass) {
            $adapter = $this->container->make($decoratorClass, ['inner' => $adapter]);
        }

        return $adapter;
    }

    /**
     * Check if platform adapter is registered.
     *
     * @param PlatformType $platform
     * @return bool
     */
    public function has(PlatformType $platform): bool
    {
        return isset($this->adapters[$platform->value]);
    }
}
