<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => true, // Should be set to false in production
                'logError'            => false,
                'logErrorDetails'     => false,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],

                // DB data from .env file (this is unused for now)
                'db' => [
                    'host'     => $_ENV['DB_HOST'] ?? 'MySQL-8.0',
                    'database' => $_ENV['DB_NAME'] ?? 'slim',
                    'username' => $_ENV['DB_USERNAME'] ?? 'test',
                    'password' => $_ENV['DB_PASSWORD'] ?? 'test',
                ]
            ]);
        },

        // set DB connection (this is unused for now)
        PDO::class => function (\Psr\Container\ContainerInterface $container) {
            // Достаем массив 'db' из настроек выше
            $settings = $container->get(SettingsInterface::class)->get('db');

            // simple connection string (DSN)
            $dsn = "mysql:host={$settings['host']};dbname={$settings['database']};charset=utf8mb4";

            // return standard PDO
            return new PDO($dsn, $settings['username'], $settings['password'], [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION
            ]);
        }

    ]);
};
