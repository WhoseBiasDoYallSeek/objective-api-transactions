<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;

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

$makeReq = function($method, $path, $headers, $bodyArr) {
    $uri = new Uri('', '', 80, $path);
    $handle = fopen('php://temp', 'w+');
    $stream = (new StreamFactory())->createStreamFromResource($handle);
    $h = new Headers();
    foreach ($headers as $k => $v) { $h->addHeader($k, $v); }
    $req = new SlimRequest($method, $uri, $h, [], [], $stream);
    if ($bodyArr !== null) {
        $req->getBody()->write(json_encode($bodyArr));
        $req->getBody()->rewind();
    }
    return $req;
};

// create account
$req = $makeReq('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'], ['numero_conta' => 777, 'saldo' => 100]);
$res = $app->handle($req);
echo "Create status: " . $res->getStatusCode() . "\n";

$tx = ['forma_pagamento' => 'P', 'numero_conta' => 777, 'valor' => 10];
$headers = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json', 'Idempotency-Key' => 'idem-123'];

$req1 = $makeReq('POST', '/transacao', $headers, $tx);
$res1 = $app->handle($req1);
echo "First tx status: " . $res1->getStatusCode() . " body: " . (string)$res1->getBody() . "\n";

$req2 = $makeReq('POST', '/transacao', $headers, $tx);
$res2 = $app->handle($req2);
echo "Second tx status: " . $res2->getStatusCode() . " body: " . (string)$res2->getBody() . "\n";

$repo = $container->get(\App\Domain\Account\AccountRepository::class);
echo "AccountRepo class: " . get_class($repo) . "\n";
echo "Balance now: " . $repo->findAccountOfNumber(777)->getSaldo() . "\n";
