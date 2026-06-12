<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | is null, Horizon will reside under the same domain as the rest of the
    | application. Otherwise, you can specify the subdomain here.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. You may
    | change this path to anything you like. Note that the path will not
    | affect the routes defined in your application's routes file.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store its
    | metadata. This connection should reside in your config/database.php
    | file and must be dedicated to Horizon's use.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing Horizon data in Redis. You may
    | change this prefix to anything you like. Note that the prefix will
    | not affect the cache prefix defined in your config/cache.php file.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply remove this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when a queue wait time is considered
    | long. The wait times are in seconds. If a queue wait time exceeds the
    | configured threshold, a "long wait" event will be fired.
    |
    */

    'waits' => [
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) completed jobs, failed
    | jobs, and monitored jobs will be kept. You may adjust these values
    | according to the scale of your application and database limits.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Trimming Times
    |--------------------------------------------------------------------------
    |
    | This option configures for how long (in minutes) the snapshots of your
    | metrics will be kept. Snapshots are taken every 5 minutes and are
    | used to generate the graphs shown in the Horizon dashboard.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Events
    |--------------------------------------------------------------------------
    |
    | This option allows you to enable or disable fast events. When enabled,
    | Horizon will use a more performant mechanism to push events to the
    | browser. This requires the Swoole or RoadRunner drivers to work.
    |
    */

    'fast_events' => true,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit
    |--------------------------------------------------------------------------
    |
    | This option defines the memory limit (in megabytes) that each supervisor
    | process is allowed to consume. If a supervisor process exceeds this
    | limit, it will automatically terminate and restart.
    |
    */

    'memory_limit' => 64,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the queue worker environments for your application.
    | You can configure the connection, queue names, process limits, and
    | timeout values for each environment that your application runs in.
    |
    */

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
                'minProcesses' => 1,
                'tries' => 3,
                'timeout' => 60,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 3,
                'minProcesses' => 1,
                'tries' => 3,
                'timeout' => 60,
            ],
        ],
    ],

];
