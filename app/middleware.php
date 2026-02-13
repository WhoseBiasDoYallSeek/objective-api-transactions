<?php

declare(strict_types=1);

use App\Application\Middleware\SessionMiddleware;
use Slim\App;

return function (App $app) {
    // Parse JSON/form bodies for actions that rely on getParsedBody()
    $app->addBodyParsingMiddleware();

    // Reject non-JSON requests for GET/POST/PUT/PATCH
    $app->add(\App\Application\Middleware\JsonOnlyMiddleware::class);

    // Idempotency middleware for POST /transacao
    $app->add(\App\Application\Middleware\IdempotencyMiddleware::class);

    // Session middleware left as optional (uses HTTP_AUTHORIZATION to start session)
    $app->add(SessionMiddleware::class);
};
