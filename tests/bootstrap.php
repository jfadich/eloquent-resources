<?php


use jfadich\EloquentResources\ResourceManager;

require __DIR__ . '/../vendor/autoload.php';
require 'test_classes.php';

if( ! function_exists('config')) {
    function config($key = null) {
        $config = include __DIR__ . '/../config/config.php';
        $config = ['transformers' => $config];

        if($key !== null)
            return array_get($config, $key);

        return $config;
    }
}

if( ! function_exists('app')) {
    function app($make) {
        static $manager;

        if($make === ResourceManager::class) {
            if ($manager === null) {
                $namespaces = config('transformers.namespaces');
                $manager = new ResourceManager(new \League\Fractal\Manager, new \Illuminate\Http\Request, $namespaces);
            }

            return $manager;
        }

        return null;
    }
}
