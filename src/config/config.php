<?php

return [

    'namespaces' => [
        //        'controllers' => "App\Http\Controllers",
        'controllers' => "Modules\{module}\App\Http\Controllers\{module}",
        //        'models' => "App\Models",
        'models' => "Modules\{module}\App\Models",
        //        'actions' => "App\Actions",
        'actions' => "Modules\Api\App\Features\{module}",
        //        'requests' => "App\Http\Requests",
        'requests' => "Modules\Api\App\Http\Requests\{module}",
        //        'resources' => "App\Http\Resources",
        'resources' => "Modules\Api\App\Http\Resources\{module}",

        //        'translations' => "",
        'translations' => '{module}::',
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
