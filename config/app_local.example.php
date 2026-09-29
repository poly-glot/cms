<?php

declare(strict_types=1);

use function Cake\Core\env;

return [
    'debug' => filter_var(env('DEBUG', true), \FILTER_VALIDATE_BOOLEAN),

    'Security' => [
        'salt' => env('SECURITY_SALT', '__SALT__'),
    ],

    'Datasources' => [
        'default' => [
            'host' => 'localhost',
            'username' => 'my_app',
            'password' => 'secret',
            'database' => 'my_app',
            'url' => env('DATABASE_URL', null),
        ],

        'test' => [
            'host' => 'localhost',
            'username' => 'my_app',
            'password' => 'secret',
            'database' => 'test_myapp',
            'url' => env('DATABASE_TEST_URL', 'sqlite://127.0.0.1/tmp/tests.sqlite'),
        ],
    ],

    'EmailTransport' => [
        'default' => [
            'className' => 'Debug',
        ],
    ],
];
