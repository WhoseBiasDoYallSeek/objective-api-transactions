<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use App\Application\Settings\SettingsInterface;
use Psr\Log\LoggerInterface;

// Build container (reuse app settings)
$containerBuilder = new ContainerBuilder();
$settings = require __DIR__ . '/../app/settings.php';
$dependencies = require __DIR__ . '/../app/dependencies.php';
$repositories = require __DIR__ . '/../app/repositories.php';

$settings($containerBuilder);
$dependencies($containerBuilder);
$repositories($containerBuilder);

$container = $containerBuilder->build();

/** @var \App\Application\Settings\SettingsInterface $appSettings */
$appSettings = $container->get(SettingsInterface::class);
$db = $appSettings->get('db');

if (($db['driver'] ?? null) !== 'sqlite') {
    fwrite(STDERR, "Database driver is not sqlite — aborting.\n");
    exit(1);
}

$databasePath = $db['database'];
if (!is_dir(dirname($databasePath))) {
    mkdir(dirname($databasePath), 0755, true);
}

// create file if not exists
if (!file_exists($databasePath)) {
    touch($databasePath);
}

// ensure PDO SQLite driver is available before creating a PDO instance
if (!extension_loaded('pdo_sqlite') && !in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "pdo_sqlite is not available in this PHP runtime. Enable the extension and re-run this script.\n");
    fwrite(STDERR, "On Debian/Ubuntu: sudo apt install php-sqlite3 && restart your web/cli php service.\n");
    exit(2);
}

$pdo = new \PDO('sqlite:' . $databasePath);
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

// create schema if needed
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL
    )'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS accounts (
        numero_conta INTEGER PRIMARY KEY,
        saldo REAL NOT NULL
    )'
);


// seed accounts if none exist
$existingAcc = $pdo->query('SELECT COUNT(1) as c FROM accounts')->fetch(\PDO::FETCH_ASSOC);
if ((int)$existingAcc['c'] === 0) {
    $stmt = $pdo->prepare('INSERT INTO accounts (numero_conta, saldo) VALUES (:numero_conta, :saldo)');
    $stmt->execute(['numero_conta' => 234, 'saldo' => 180.37]);
}

echo "Seed complete — {$databasePath}\n";
