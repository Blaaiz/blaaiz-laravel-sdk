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

            return new Blaaiz([
                'api_key' => $config['api_key'] ?? '',
                'client_id' => $config['client_id'] ?? '',
                'client_secret' => $config['client_secret'] ?? '',
                'oauth_scope' => $config['oauth_scope'] ?? '*',
                'base_url' => $config['base_url'],
                'timeout' => $config['timeout'],
            ]);
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