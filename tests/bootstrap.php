<?php


use Illuminate\Support\Arr;
use jfadich\EloquentResources\ResourceManager;

require __DIR__ . '/../vendor/autoload.php';
require 'test_classes.php';

if( ! function_exists('config')) {
    function config($key = null) {
        $config = include __DIR__ . '/../config/config.php';
        $config = ['resources' => $config];

        if($key !== null)
            return Arr::get($config, $key);

        return $config;
    }
}

if( ! function_exists('app')) {
    function app($make) {
        static $manager;

        if($make === ResourceManager::class) {
            if ($manager === null) {
                $namespaces = config('resources.namespaces');
                $manager = new ResourceManager(new \League\Fractal\Manager, new \Illuminate\Http\Request, $namespaces);
            }

            return $manager;
        }

        return null;
    }
}
