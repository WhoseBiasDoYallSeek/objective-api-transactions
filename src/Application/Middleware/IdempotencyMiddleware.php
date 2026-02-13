<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\Idempotency\IdempotencyRepository;
use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class IdempotencyMiddleware implements Middleware
{
    private IdempotencyRepository $repo;

    private int $ttlSeconds;

    public function __construct(IdempotencyRepository $repo, \App\Application\Settings\SettingsInterface $settings)
    {
        $this->repo = $repo;
        $this->ttlSeconds = (int) ($settings->get('idempotency_ttl_seconds') ?? 86400);
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Only enforce for POST /transacao
        $path = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());
        if (!($method === 'POST' && $path === '/transacao')) {
            return $handler->handle($request);
        }

        $key = $request->getHeaderLine('Idempotency-Key');
        if ($key === '') {
            // no key provided: proceed normally
            return $handler->handle($request);
        }

        $body = $request->getParsedBody() ?? [];
        $requestHash = hash('sha256', $method . '|' . $path . '|' . json_encode($body));

        $existing = $this->repo->find($key);
        if ($existing !== null) {
            // check TTL
            $createdAt = isset($existing['created_at']) ? strtotime($existing['created_at']) : null;
            if ($createdAt !== null && (time() - $createdAt) > $this->ttlSeconds) {
                // expired — remove and continue as if not present
                $this->repo->delete($key);
                $existing = null;
            }
        }

        if ($existing !== null) {
            // If same request, return stored response; otherwise conflict
            if ($existing['request_hash'] === $requestHash) {
                $resp = new \Slim\Psr7\Response($existing['response_code']);
                $resp->getBody()->write($existing['response_body']);
                return $resp->withHeader('Content-Type', 'application/json')
                            ->withHeader('Idempotency-Status', 'replayed');
            }

            $resp = new \Slim\Psr7\Response(409);
            $payload = new ActionPayload(409, null, new ActionError(ActionError::VALIDATION_ERROR, 'Idempotency key already used with different request'));
            $resp->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $resp->withHeader('Content-Type', 'application/json');
        }

        // Not stored yet: execute handler, store result if successful
        $response = $handler->handle($request);

        $status = $response->getStatusCode();
        $bodyStr = (string) $response->getBody();

        // Only persist successful responses (2xx)
        if ($status >= 200 && $status < 300) {
            $this->repo->save($key, $requestHash, $status, $bodyStr);
        }

        return $response->withHeader('Idempotency-Status', 'stored');
    }
}
