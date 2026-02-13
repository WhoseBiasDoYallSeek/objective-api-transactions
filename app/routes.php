<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    // Banking API: contas e transacoes
    $app->post('/conta', \App\Application\Actions\Account\CreateAccountAction::class);
    $app->get('/conta', \App\Application\Actions\Account\GetAccountAction::class);

    $app->post('/transacao', \App\Application\Actions\Account\TransactionAction::class);
};
