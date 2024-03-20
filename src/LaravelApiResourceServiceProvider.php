<?php

namespace SingleQuote\LaravelApiResource;

use Illuminate\Support\ServiceProvider;
use SingleQuote\LaravelApiResource\Commands\MakeApiResource;

use function config_path;

class LaravelApiResourceServiceProvider extends ServiceProvider
{
    /**
     * Commands.
     *
     * @var array
     */
    protected $commands = [
        MakeApiResource::class,
    ];

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('laravel-api-resource.php'),
        ], 'laravel-api-resource');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        //config
        $this->mergeConfigFrom(
            __DIR__.'/config/config.php',
            'laravel-api-resource'
        );

        $this->commands($this->commands);
    }
}
