<?php

namespace jfadich\EloquentResources\Providers;

use jfadich\EloquentResources\Console\MakeTransformableCommand;
use jfadich\EloquentResources\Console\MakeTransformerCommand;
use jfadich\EloquentResources\Console\MakePresenterCommand;
use jfadich\EloquentResources\ResourceManager;
use Illuminate\Support\ServiceProvider;
use League\Fractal\Manager as Fractal;
use Illuminate\Http\Request;

class LaravelServiceProvider extends ServiceProvider
{
    /**
     * @var Fractal
     */
    protected $fractal;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Bootstrap any application services.
     *
     * @param Fractal $fractal
     * @param Request $request
     * @return void
     */
    public function boot(Fractal $fractal, Request $request)
    {
        $this->fractal = $fractal;
        $this->request = $request;

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

            return new ResourceManager($this->fractal, $this->request, $namespaces);
        });
    }
}