<?php

namespace jfadich\EloquentResources\Providers;


use Illuminate\Support\ServiceProvider;
use jfadich\EloquentResources\Console\MakePresenterCommand;
use jfadich\EloquentResources\Console\MakeTransformableCommand;
use jfadich\EloquentResources\Console\MakeTransformerCommand;
use jfadich\EloquentResources\TransformationManager;

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

        $this->app->singleton(TransformationManager::class, function ($app) {
            $namespaces = config('transformers.namespaces');

            return new TransformationManager($namespaces['models'], $namespaces['transformers'], $namespaces['presenters']);
        });
    }
}