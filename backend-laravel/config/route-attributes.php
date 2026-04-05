<?php

return [
    'enabled' => true,

    'directories' => [
        app_path('Modules') => [
            'prefix' => 'api/admin',
            'middleware' => ['api', 'anno.auth', 'anno.op'],
            'patterns' => ['*Controller.php'],
            'not_patterns' => [],
        ],
    ],

    'middleware' => [
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    'scope-bindings' => null,
];
