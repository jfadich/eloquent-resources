<?php

return [
    'namespaces' => [
        'models' => 'App',
        'transformers' => 'App\\Transformers',
        'presenters' => 'App\\Presenters'
    ],

    'countName' => 'limit',

    'defaultCount' => 25,

    'includesName' => 'with',

    'baseTransformer' => jfadich\JsonResponder\Transformer::class,

    'basePresenter' => jfadich\JsonResponder\Presenter::class
];