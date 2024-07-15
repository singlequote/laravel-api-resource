<?php

return [

    /*
    |--------------------------------------------------------------------------
    | namespaces
    |--------------------------------------------------------------------------
    | Set the namespace directories to store generated files
   */
    'namespaces' => [
        'controllers' => "App\Http\Controllers",
        'models' => "App\Models",
        'actions' => "App\Actions",
        'requests' => "App\Http\Requests",
        'resources' => "App\Http\Resources",
        'translations' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude columns
    |--------------------------------------------------------------------------
    | This will exlude columns used to render the request or resource files. For example, a password should not be visible in either resources or requests
    | For example a password should not be visible in either resources or requests
   */
    'exclude' => [
        'resources' => [],
        'requests' => [],
    ],

    'api' => [
        /*
        |--------------------------------------------------------------------------
        | Limit
        |--------------------------------------------------------------------------
        | Maximum results per page the api can return
       */
        'limit' => 1000,
    ],

];
