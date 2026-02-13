<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use App\Application\Settings\SettingsInterface;

$ttlArg = $argv[1] ?? null;

$containerBuilder = new ContainerBuilder();
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);
$repositories = require __DIR__ . '/../app/repositories.php';
$repositories($containerBuilder);
$container = $containerBuilder->build();

/** @var \App\Domain\Idempotency\IdempotencyRepository $repo */
$repo = $container->get(\App\Domain\Idempotency\IdempotencyRepository::class);

$settingsSvc = $container->get(SettingsInterface::class);
$defaultTtl = (int) ($settingsSvc->get('idempotency_ttl_seconds') ?? 86400);
$ttl = $ttlArg !== null ? (int)$ttlArg : $defaultTtl;

$deleted = $repo->pruneExpired($ttl);

echo "Pruned $deleted idempotency entr" . ($deleted === 1 ? "y" : "ies") . " (TTL={$ttl}s)\n";
