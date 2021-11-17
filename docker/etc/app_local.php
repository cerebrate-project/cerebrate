<?php
$db = [
    'username' => env('CEREBRATE_DB_USERNAME', 'cerebrate'),
    'password' => env('CEREBRATE_DB_PASSWORD', ''),
    'host'     => env('CEREBRATE_DB_HOST', 'localhost'),
    'database' => env('CEREBRATE_DB_NAME', 'cerebrate'),
];

// non-default port can be set on demand - otherwise the DB driver will choose the default
if (!empty(env('CEREBRATE_DB_PORT'))) {
    $db['port'] = env('CEREBRATE_DB_PORT');
}

// If not using the default 'public' schema with the PostgreSQL driver set it here.
if (!empty(env('CEREBRATE_DB_SCHEMA'))) {
    $db['schema'] = env('CEREBRATE_DB_SCHEMA');
}

return [
    'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),

    'Security' => [
        'salt' => env('CEREBRATE_SECURITY_SALT'),
    ],

    'Datasources' => [
        'default' => $db,
    ],

    'EmailTransport' => [
        'default' => [
            // host could be ssl://smtp.gmail.com then set port to 465
            'host' => env('CEREBRATE_EMAIL_HOST', 'localhost'),
            'port' => env('CEREBRATE_EMAIL_PORT', 25),
            'username' => env('CEREBRATE_EMAIL_USERNAME', null),
            'password' => env('CEREBRATE_EMAIL_PASSWORD', null),
            'tls' => env('CEREBRATE_EMAIL_TLS', null)
        ],
    ],
    'Cerebrate' => [
    'open' => [],
        'dark' => 0
    ]
];
