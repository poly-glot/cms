<?php

declare(strict_types=1);

namespace App\Console;

use Cake\Utility\Security;
use Composer\IO\IOInterface;
use Composer\Script\Event;

class Installer
{
    public const WRITABLE_DIRS = [
        'logs',
        'tmp',
        'tmp/cache',
        'tmp/cache/models',
        'tmp/cache/persistent',
        'tmp/cache/views',
        'tmp/sessions',
        'tmp/tests',
    ];

    public static function postInstall(Event $event): void
    {
        $io = $event->getIO();

        $rootDir = dirname(__DIR__, 2);

        static::createAppLocalConfig($rootDir, $io);
        static::createWritableDirectories($rootDir, $io);

        static::setFolderPermissions($rootDir, $io);
        static::setSecuritySalt($rootDir, $io);
    }

    public static function createAppLocalConfig(string $dir, IOInterface $io): void
    {
        $appLocalConfig = $dir . '/config/app_local.php';
        $appLocalConfigTemplate = $dir . '/config/app_local.example.php';
        if (!file_exists($appLocalConfig)) {
            copy($appLocalConfigTemplate, $appLocalConfig);
            $io->write('Created `config/app_local.php` file');
        }
    }

    public static function createWritableDirectories(string $dir, IOInterface $io): void
    {
        foreach (static::WRITABLE_DIRS as $path) {
            $path = $dir . '/' . $path;
            if (!file_exists($path)) {
                mkdir($path);
                $io->write('Created `' . $path . '` directory');
            }
        }
    }

    public static function setFolderPermissions(string $dir, IOInterface $io): void
    {
        $changePerms = static function (string $path) use ($io): void {
            $currentPerms = fileperms($path) & 0777;
            $worldWritable = $currentPerms | 0007;
            if ($worldWritable === $currentPerms) {
                return;
            }

            $res = chmod($path, $worldWritable);
            if ($res) {
                $io->write('Permissions set on ' . $path);
            } else {
                $io->write('Failed to set permissions on ' . $path);
            }
        };

        $walker = static function (string $dir) use (&$walker, $changePerms): void {
            $files = array_diff(scandir($dir) ?: [], ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;

                if (!is_dir($path)) {
                    continue;
                }

                $changePerms($path);
                $walker($path);
            }
        };

        $walker($dir . '/tmp');
        $changePerms($dir . '/tmp');
        $changePerms($dir . '/logs');
    }

    public static function setSecuritySalt(string $dir, IOInterface $io): void
    {
        $config = $dir . '/config/app_local.php';
        $content = file_get_contents($config);
        if ($content === false) {
            $io->write('Config file not readable or not found: config/app_local.php');

            return;
        }

        $newKey = hash('sha256', Security::randomBytes(64));
        $content = str_replace('__SALT__', $newKey, $content, $count);

        if ($count === 0) {
            $io->write('No Security.salt placeholder to replace.');

            return;
        }

        $result = file_put_contents($config, $content);
        if ($result) {
            $io->write('Updated Security.salt value in config/app_local.php');

            return;
        }
        $io->write('Unable to update Security.salt value.');
    }
}
