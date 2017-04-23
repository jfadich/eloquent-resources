<?php


use jfadich\EloquentResources\TransformationManager;

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

        if($make === TransformationManager::class) {
            if ($manager === null) {
                $config = config('transformers.namespaces');
                $manager = new TransformationManager($config['models'], $config['transformers'], $config['presenters']);
            }

            return $manager;
        }

        return null;
    }
}
