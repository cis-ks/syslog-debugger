<?php

return [

    /*
    |--------------------------------------------------------------------------
    | UDP Syslog Listener (RFC 3164 / RFC 5424)
    |--------------------------------------------------------------------------
    |
    | Standard UDP syslog port is 514.
    |
    */

    'udp' => [
        'enabled' => env('SYSLOG_UDP_ENABLED', true),
        'host' => env('SYSLOG_UDP_HOST', '0.0.0.0'),
        'port' => (int) env('SYSLOG_UDP_PORT', 514),
    ],

    /*
    |--------------------------------------------------------------------------
    | TLS Syslog Listener (RFC 5425 / RFC 5424)
    |--------------------------------------------------------------------------
    |
    | Standard TLS syslog port is 6514.
    |
    */

    'tls' => [
        'enabled' => env('SYSLOG_TLS_ENABLED', true),
        'host' => env('SYSLOG_TLS_HOST', '0.0.0.0'),
        'port' => (int) env('SYSLOG_TLS_PORT', 6514),
        'cert' => env('SYSLOG_TLS_CERT', storage_path('certs/syslog.crt')),
        'key' => env('SYSLOG_TLS_KEY', storage_path('certs/syslog.key')),
        'passphrase' => env('SYSLOG_TLS_PASSPHRASE', null),
        'verify_peer' => env('SYSLOG_TLS_VERIFY_PEER', false),
        'allow_self_signed' => env('SYSLOG_TLS_ALLOW_SELF_SIGNED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Syslog Storage / Dispatch
    |--------------------------------------------------------------------------
    |
    | Configure whether received syslog messages should trigger Laravel events.
    |
    */

    'dispatch_events' => env('SYSLOG_DISPATCH_EVENTS', true),

];
