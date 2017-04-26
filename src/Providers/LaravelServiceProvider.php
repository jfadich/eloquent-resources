<?php

namespace jfadich\EloquentResources\Providers;


use Illuminate\Support\ServiceProvider;
use jfadich\EloquentResources\Console\MakePresenterCommand;
use jfadich\EloquentResources\Console\MakeTransformableCommand;
use jfadich\EloquentResources\Console\MakeTransformerCommand;
use jfadich\EloquentResources\ResourceManager;

class LaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('transformers.php'),
        ]);

        if($this->app->runningInConsole()) {
            $this->commands([
                MakeTransformableCommand::class,
                MakeTransformerCommand::class,
                MakePresenterCommand::class
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/config.php', 'transformers'
        );

        $this->app->singleton(ResourceManager::class, function ($app) {
            $namespaces = config('transformers.namespaces');

            return new ResourceManager($namespaces['models'], $namespaces['transformers'], $namespaces['presenters']);
        });
    }
}