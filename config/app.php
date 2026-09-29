<?php

declare(strict_types=1);

use Cake\Cache\Engine\FileEngine;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Log\Engine\FileLog;
use Cake\Mailer\Transport\MailTransport;

use function Cake\Core\env;

$mysqlConnection = [
    'className' => Connection::class,
    'driver' => Mysql::class,
    'persistent' => false,
    'timezone' => 'UTC',
    'encoding' => 'utf8mb4',
    'flags' => env('DB_SSL') ? [
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ] : [],
    'cacheMetadata' => true,
    'log' => false,
    'quoteIdentifiers' => false,
    'host' => env('DB_HOST', 'db'),
    'username' => env('DB_USER', 'cms'),
    'password' => env('DB_PASS', 'cms'),
    'database' => env('DB_NAME', 'cms'),
];

return [
    'debug' => filter_var(env('DEBUG', false), \FILTER_VALIDATE_BOOLEAN),

    'App' => [
        'namespace' => 'App',
        'encoding' => env('APP_ENCODING', 'UTF-8'),
        'defaultLocale' => env('APP_DEFAULT_LOCALE', 'en_US'),
        'defaultTimezone' => env('APP_DEFAULT_TIMEZONE', 'UTC'),
        'base' => false,
        'dir' => 'src',
        'webroot' => 'webroot',
        'wwwRoot' => WWW_ROOT,
        'fullBaseUrl' => env('APP_FULL_BASE_URL', false),
        'imageBaseUrl' => 'img/',
        'cssBaseUrl' => 'css/',
        'jsBaseUrl' => 'js/',
        'paths' => [
            'plugins' => [ROOT . DS . 'plugins' . DS],
            'templates' => [ROOT . DS . 'templates' . DS],
            'locales' => [RESOURCES . 'locales' . DS],
        ],
        'uploads' => [
            'path' => env('UPLOADS_PATH', ROOT . DS . 'storage' . DS . 'uploads'),
        ],
        'media' => [
            'publicBase' => env('MEDIA_PUBLIC_BASE', ''),
        ],
    ],

    'Asset' => [
        'timestamp' => 'force',
    ],

    'Security' => [
        'salt' => env('SECURITY_SALT'),
    ],

    'Cache' => [
        'default' => [
            'className' => FileEngine::class,
            'path' => CACHE,
            'url' => env('CACHE_DEFAULT_URL', null),
        ],

        'menus' => [
            'className' => FileEngine::class,
            'path' => CACHE,
            'prefix' => 'cms_menu_',
            'duration' => '+1 day',
        ],

        'ratelimit' => [
            'className' => FileEngine::class,
            'path' => CACHE,
            'prefix' => 'cms_rl_',
            'duration' => '+1 hour',
        ],

        '_cake_translations_' => [
            'className' => FileEngine::class,
            'prefix' => 'myapp_cake_translations_',
            'path' => CACHE . 'persistent' . DS,
            'serialize' => true,
            'duration' => '+1 years',
            'url' => env('CACHE_CAKECORE_URL', null),
        ],

        '_cake_model_' => [
            'className' => FileEngine::class,
            'prefix' => 'myapp_cake_model_',
            'path' => CACHE . 'models' . DS,
            'serialize' => true,
            'duration' => '+1 years',
            'url' => env('CACHE_CAKEMODEL_URL', null),
        ],
    ],

    'Error' => [
        'errorLevel' => \E_ALL,
        'exceptionRenderer' => 'Cake\\Error\\Renderer\\WebExceptionRenderer',
        'skipLog' => [],
        'log' => true,
        'trace' => false,
        'ignoredDeprecationPaths' => [],
        'traceFormat' => null,
    ],

    'Debugger' => [
        'editor' => 'phpstorm',
    ],

    'EmailTransport' => [
        'default' => [
            'className' => MailTransport::class,
            'host' => 'localhost',
            'port' => 25,
            'timeout' => 30,
            'client' => null,
            'tls' => false,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],

    'Email' => [
        'default' => [
            'transport' => 'default',
            'from' => 'you@localhost',
        ],
    ],

    'Datasources' => [
        'default' => $mysqlConnection,
        'test' => $mysqlConnection,
    ],

    'Log' => [
        'debug' => [
            'className' => FileLog::class,
            'path' => LOGS,
            'file' => 'debug',
            'url' => env('LOG_DEBUG_URL', null),
            'scopes' => null,
            'levels' => ['notice', 'info', 'debug'],
        ],
        'error' => [
            'className' => FileLog::class,
            'path' => LOGS,
            'file' => 'error',
            'url' => env('LOG_ERROR_URL', null),
            'scopes' => null,
            'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
        ],
    ],

    'Session' => [
        'cookie' => '__session',
        'defaults' => env('SESSION_DEFAULTS', 'php'),
        'ini' => [
            'session.cookie_secure' => str_starts_with((string) env('APP_FULL_BASE_URL', ''), 'https://'),
        ],
    ],
];
