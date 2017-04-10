<?php

namespace jfadich\JsonResponder\Providers;


use Illuminate\Support\ServiceProvider;
use jfadich\JsonResponder\Console\MakePresenterCommand;
use jfadich\JsonResponder\Console\MakeTransformerCommand;
use jfadich\JsonResponder\TransformationManager;

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
                MakePresenterCommand::class,
                MakeTransformerCommand::class
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