<?php

return [
    /*
    * This setting controls whether data should be sent to peek.
    *
    * By default, `peek()` will only transmit data in non-production environments.
    */
    'enable' => env('PEEK_ENABLED', true),

    /*
    * When enabled, all cache events  will automatically be sent to peek.
    */
    'send_cache_to_peek' => env('SEND_CACHE_TO_PEEK', false),

    /*
    * When enabled, all things passed to `dump` or `dd`
    * will be sent to peek as well.
    */
    'send_dumps_to_peek' => env('SEND_DUMPS_TO_PEEK', true),

    /*
    * When enabled all job events will automatically be sent to peek.
    */
    'send_jobs_to_peek' => env('SEND_JOBS_TO_PEEK', false),

    /*
    * When enabled, all things logged to the application log
    * will be sent to peek as well.
    */
    'send_log_calls_to_peek' => env('SEND_LOG_CALLS_TO_PEEK', true),

    /*
    * When enabled, all queries will automatically be sent to peek.
    */
    'send_queries_to_peek' => env('SEND_QUERIES_TO_PEEK', false),

    /**
     * When enabled, all duplicate queries will automatically be sent to peek.
     */
    'send_duplicate_queries_to_peek' => env('SEND_DUPLICATE_QUERIES_TO_PEEK', false),

    /*
     * When enabled, slow queries will automatically be sent to peek.
     */
    'send_slow_queries_to_peek' => env('SEND_SLOW_QUERIES_TO_PEEK', false),

    /**
     * Queries that are longer than this number of milliseconds will be regarded as slow.
     */
    'slow_query_threshold_in_ms' => env('PEEK_SLOW_QUERY_THRESHOLD_IN_MS', 500),

    /*
    * When enabled, all requests made to this app will automatically be sent to peek.
    */
    'send_requests_to_peek' => env('SEND_REQUESTS_TO_PEEK', false),

    /**
     * When enabled, all Http Client requests made by this app will be automatically sent to peek.
     */
    'send_http_client_requests_to_peek' => env('SEND_HTTP_CLIENT_REQUESTS_TO_PEEK', false),

    /*
    * When enabled, all views that are rendered automatically be sent to peek.
    */
    'send_views_to_peek' => env('SEND_VIEWS_TO_PEEK', false),

    /*
     * When enabled, all exceptions will be automatically sent to peek.
     */
    'send_exceptions_to_peek' => env('SEND_EXCEPTIONS_TO_PEEK', true),

    /*
     * When enabled, all deprecation notices will be automatically sent to peek.
     */
    'send_deprecated_notices_to_peek' => env('SEND_DEPRECATED_NOTICES_TO_PEEK', false),

    /*
    * The host used to communicate with the peek app.
    * When using Docker on Mac or Windows, you can replace localhost with 'host.docker.internal'
    * When using Docker on Linux, you can replace localhost with '172.17.0.1'
    * When using Homestead with the VirtualBox provider, you can replace localhost with '10.0.2.2'
    * When using Homestead with the Parallels provider, you can replace localhost with '10.211.55.2'
    */
    'host' => env('PEEK_HOST', 'localhost'),

    /*
    * The port number used to communicate with the peek app.
    */
    'port' => env('PEEK_PORT', 44315),

    /*
     * Absolute base path for your sites or projects in Homestead,
     * Vagrant, Docker, or another remote development server.
     */
    'remote_path' => env('PEEK_REMOTE_PATH', null),

    /*
     * Absolute base path for your sites or projects on your local
     * computer where your IDE or code editor is running on.
     */
    'local_path' => env('PEEK_LOCAL_PATH', null),

    /*
     * When this setting is enabled, the package will not try to format values sent to peek.
     */
    'always_send_raw_values' => false,
];
