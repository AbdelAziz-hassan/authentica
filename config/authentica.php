<?php

return [
    'host'            => env('AUTHENTICA_HOST', 'https://api.authentica.sa/api/v2'),
    'api_key'         => env('AUTHENTICA_API_KEY', ''),

    'timeout'         => env('AUTHENTICA_TIMEOUT', 10),
    'connect_timeout' => env('AUTHENTICA_CONNECT_TIMEOUT', 5),
    'webhooks' => [
        'nafath' => [
            // Turn the auto-registered webhook route on/off and set its path
            'enabled' => env('AUTHENTICA_NAFATH_WEBHOOK_ENABLED', false),
            'path'    => env('AUTHENTICA_NAFATH_WEBHOOK_PATH', '/webhooks/authentica/nafath'),

            /**
             * Choose ONE auth mode:
             * - body_password : Compare payload["Password"] to a server-side secret.
             * - basic         : Compare incoming Basic Auth header to configured username/password (label+value).
             * - jwt_login     : Call your login endpoint using configured field "labels" and "values", take token from response,
             *                   and compare with incoming Bearer token.
             */
            'auth' => env('AUTHENTICA_NAFATH_WEBHOOK_AUTH', 'body_password'),

            // ========================
            // 1) BODY PASSWORD MODE
            // ========================
            'body_password' => [
                // The server-side secret we expect to see in payload["Password"]
                'password_value' => env('AUTHENTICA_NAFATH_PASSWORD_VALUE', null),
                // If payload uses a custom property name instead of "Password", set the label here
                'password_label' => env('AUTHENTICA_NAFATH_PASSWORD_LABEL', 'Password'),
            ],

            // ========================
            // 2) BASIC AUTH MODE
            // ========================
            'basic' => [
                // Accept any label names you prefer, but Basic ultimately uses a single header "Authorization"
                // We keep label/value for consistency with your requirement.
                'username_label' => env('AUTHENTICA_NAFATH_BASIC_USER_LABEL', 'username'),
                'username_value' => env('AUTHENTICA_NAFATH_BASIC_USER_VALUE', null),

                'password_label' => env('AUTHENTICA_NAFATH_BASIC_PASS_LABEL', 'password'),
                'password_value' => env('AUTHENTICA_NAFATH_BASIC_PASS_VALUE', null),
            ],
        ],
    ],
    'circuit' => [
        // Turn the feature on/off globally
        'enabled' => env('AUTHENTICA_CIRCUIT_ENABLED', false),

        // Storage driver for breaker state:
        // - cache  : uses Laravel Cache (Redis/memcached/file…)
        // - array  : in-memory (per PHP process) — for apps without Cache
        // - custom : your own class implementing \AbdelAzizHassan\Authentica\Support\CircuitStore
        'driver'  => env('AUTHENTICA_CIRCUIT_DRIVER', 'cache'),

        // Only when driver=custom: fully-qualified class name of your store
        'store'   => env('AUTHENTICA_CIRCUIT_STORE', null),

        // Namespace key for this upstream
        'key' => 'authentica_v2',

        // Trip/open settings
        'failure_threshold'            => env('AUTHENTICA_CIRCUIT_FAILURES', 5),  // consecutive failures before open
        'open_seconds'                 => env('AUTHENTICA_CIRCUIT_OPEN', 60),     // how long to stay open before half-open probe
        'half_open_successes_to_close' => env('AUTHENTICA_CIRCUIT_SUCC', 2),      // successes in half-open needed to close

        // Which HTTP statuses count as "failure" (network error/status=0 always counts)
        'failure_statuses' => [429, 500, 502, 503, 504],
    ],

];
