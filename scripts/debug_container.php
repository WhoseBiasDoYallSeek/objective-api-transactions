<?php
require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
$settings = require __DIR__ . '/../app/settings.php';
$dependencies = require __DIR__ . '/../app/dependencies.php';
$repositories = require __DIR__ . '/../app/repositories.php';

$settings($containerBuilder);
$dependencies($containerBuilder);
$repositories($containerBuilder);

$container = $containerBuilder->build();

$a = $container->get(\App\Domain\Account\AccountRepository::class);
$b = $container->get(\App\Domain\Account\AccountRepository::class);
var_dump($a === $b);

// show class names
echo get_class($a) . PHP_EOL;
echo get_class($b) . PHP_EOL;
