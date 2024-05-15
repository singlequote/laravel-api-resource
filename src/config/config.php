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
        'resources' => [],
        'requests' => [],
    ],

    'api' => [
        'limit' => 1000,
    ],

];
