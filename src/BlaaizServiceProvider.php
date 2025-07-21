<?php

namespace Blaaiz\LaravelSdk;

use Illuminate\Support\ServiceProvider;

class BlaaizServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/blaaiz.php', 'blaaiz');

        $this->app->singleton('blaaiz', function ($app) {
            $config = $app['config']['blaaiz'];
            
            return new Blaaiz(
                $config['api_key'] ?: 'test-key', // Use fallback for tests
                [
                    'base_url' => $config['base_url'],
                    'timeout' => $config['timeout'],
                ]
            );
        });

        $this->app->alias('blaaiz', Blaaiz::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/blaaiz.php' => config_path('blaaiz.php'),
            ], 'blaaiz-config');
        }
    }

    public function provides(): array
    {
        return ['blaaiz', Blaaiz::class];
    }
}