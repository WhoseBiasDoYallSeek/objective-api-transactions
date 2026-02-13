<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class JsonOnlyMiddleware implements Middleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $method = strtoupper($request->getMethod());

        // For requests that accept a response (GET, POST, etc.) ensure Accept header prefers JSON
        $accept = $request->getHeaderLine('Accept');
        if ($accept !== '' && stripos($accept, 'application/json') === false) {
            $response = new \Slim\Psr7\Response(406);
            $payload = new ActionPayload(406, null, new ActionError(ActionError::BAD_REQUEST, 'Only application/json responses are supported.'));
            $response->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // For methods that send a body, require Content-Type application/json
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $contentType = $request->getHeaderLine('Content-Type');
            if (stripos($contentType, 'application/json') === false) {
                $response = new \Slim\Psr7\Response(415);
                $payload = new ActionPayload(415, null, new ActionError(ActionError::VALIDATION_ERROR, 'Only application/json content is accepted.'));
                $response->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return $response->withHeader('Content-Type', 'application/json');
            }
        }

        return $handler->handle($request);
    }
}
