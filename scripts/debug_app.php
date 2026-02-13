<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;

// build container like TestCase
$containerBuilder = new ContainerBuilder();
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);
$repositories = require __DIR__ . '/../app/repositories.php';
$repositories($containerBuilder);
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);
$app->addBodyParsingMiddleware();
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

// make request to POST /conta
$uri = new Uri('', '', 80, '/conta');
$handle = fopen('php://temp', 'w+');
$stream = (new StreamFactory())->createStreamFromResource($handle);
$h = new Headers();
$h->addHeader('HTTP_ACCEPT', 'application/json');
$h->addHeader('CONTENT_TYPE', 'application/json');
$req = new SlimRequest('POST', $uri, $h, [], [], $stream);
$req->getBody()->write(json_encode(['numero_conta'=>123, 'saldo'=>100]));
$req->getBody()->rewind();

$res = $app->handle($req);

echo "Status: " . $res->getStatusCode() . PHP_EOL;
echo (string)$res->getBody() . PHP_EOL;