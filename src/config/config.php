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

    'columns' => [

        /*
        |--------------------------------------------------------------------------
        | Default columns
        |--------------------------------------------------------------------------
        | These are the default columns the API will select from your database table.
        | For example, The ID, created_at and updated_at columns are present on every table by default by laravel
       */
        'default' => [
            'id',
            'created_at',
            'updated_at',
        ],
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

        'requests' => [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
        ],
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

    'search' => [
        /*
        |--------------------------------------------------------------------------
        | Lower-case wrapping
        |--------------------------------------------------------------------------
        | When true (default), search comparisons wrap the column in LOWER() to
        | force case-insensitive matching regardless of the database collation.
        |
        | If your database columns already use a case-insensitive collation
        | (e.g. MySQL *_ci), you can set this to false. Doing so drops the
        | per-row LOWER() evaluation on large scans while keeping identical
        | results. Keep it true on case-sensitive collations (e.g. MySQL *_bin)
        | and on PostgreSQL, where LIKE is case-sensitive.
       */
        'lower' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    | Below you can configure the ttl of keys to improve performance. Set to 0 to disable cache.
    | ttl is in seconds, 3600 is 1 hour.
   */
    'cache' => [
        'fillables' => 3600 * 7,
        'relations' => 3600 * 7,
    ]

];
