<?php

return [

    'namespaces' => [
        'controllers' => "App\Http\Controllers",
        'models' => "App\Models",
        'actions' => "App\Actions",
        'requests' => "App\Http\Requests",
        'resources' => "App\Http\Resources",
        'translations' => '',
    ],

    'exclude' => [
        'resources' => [
            'base_company_id',
        ],
        'requests' => [
            'base_company_id',
            'slug',
        ],
    ],

    'api' => [
        'limit' => 10,
    ],

];
