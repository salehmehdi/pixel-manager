<?php

namespace SalehSignal\PixelManager;

use Illuminate\Support\ServiceProvider;
use SalehSignal\PixelManager\Events\PixelEventCreated;
use SalehSignal\PixelManager\Listeners\DistributePixelEvent;

class PixelManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__.'/../config/pixel-manager.php',
            'pixel-manager'
        );

        // Register core service as singleton
        $this->app->singleton('pixel-manager', function ($app) {
            return new PixelManager($app);
        });

        // Bind PixelManager to container
        $this->app->bind(PixelManager::class, function ($app) {
            return $app['pixel-manager'];
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/pixel-manager.php' => config_path('pixel-manager.php'),
        ], 'pixel-manager-config');

        // Publish migrations (optional for MongoDB)
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'pixel-manager-migrations');

        // Register event listeners
        $this->app['events']->listen(
            PixelEventCreated::class,
            DistributePixelEvent::class
        );
    }
}
