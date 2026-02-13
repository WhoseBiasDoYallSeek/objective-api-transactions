<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },

        // PDO (SQLite support). The container will create the PDO instance only when db.driver is 'sqlite'.
        \PDO::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $db = $settings->get('db');

            if (($db['driver'] ?? null) !== 'sqlite') {
                throw new \RuntimeException('Only sqlite driver is configured in this skeleton.');
            }

            $dsn = sprintf('sqlite:%s', $db['database']);
            $pdo = new \PDO($dsn);
            // prefer native prepares when supported to reduce SQL injection risk
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            // ensure essential tables exist so runtime endpoints don't fail when DB file exists but schema is missing
            $pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, username TEXT, first_name TEXT, last_name TEXT)');
            $pdo->exec('CREATE TABLE IF NOT EXISTS accounts (numero_conta INTEGER PRIMARY KEY, saldo REAL NOT NULL)');
            // idempotency table to store responses for safety on retry
            $pdo->exec('CREATE TABLE IF NOT EXISTS idempotency (idempotency_key TEXT PRIMARY KEY, request_hash TEXT NOT NULL, response_code INTEGER NOT NULL, response_body TEXT NOT NULL, created_at TEXT NOT NULL)');

            return $pdo;
        },
    ]);
};
