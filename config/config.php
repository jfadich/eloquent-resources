<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    |
    | This option controls the namespaces to be used when automatically
    | resolving presenter or transformer for the given model.
    |
    */
    'namespaces'    => [
        'models'        => 'App',
        'transformers'  => 'App\\Transformers',
        'presenters'    => 'App\\Presenters'
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Parameters
    |--------------------------------------------------------------------------
    |
    | This option controls the parameter name and default value. Includes max
    | sets the recursion limit on fractal
    |
    */
    'parameters'    => [
        'count' => [
            'name'      => 'limit',
            'default'   => 25,
            'max'       => 1000
        ],
        'includes'      => [
            'name'      => 'with',
            'max'       => 5
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Base Classes
    |--------------------------------------------------------------------------
    |
    | These are the base transformer and presenter classes. Newly generated classes
    | will extend these values. Also if no presenter is found for a model,
    | this class will be used instead.
    |
    */
    'classes'       => [
        'transformer'   => jfadich\JsonResponder\Transformer::class,
        'presenter'     => jfadich\JsonResponder\Presenter::class
    ],
];