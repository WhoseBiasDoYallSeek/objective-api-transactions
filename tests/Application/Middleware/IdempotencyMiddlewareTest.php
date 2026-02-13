<?php

declare(strict_types=1);

namespace Tests\Application\Middleware;

use Tests\TestCase;

class IdempotencyMiddlewareTest extends TestCase
{
    public function testRepeatedPostWithSameKeyIsIdempotent()
    {
        $app = $this->getAppInstance();

        // create account
        $req = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req->getBody()->write(json_encode(['numero_conta' => 777, 'saldo' => 100]));
        $req->getBody()->rewind();
        $app->handle($req);

        $tx = ['forma_pagamento' => 'P', 'numero_conta' => 777, 'valor' => 10];
        $headers = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json', 'Idempotency-Key' => 'idem-123'];

        $req1 = $this->createRequest('POST', '/transacao', $headers);
        $req1->getBody()->write(json_encode($tx));
        $req1->getBody()->rewind();
        $res1 = $app->handle($req1);

        $this->assertEquals(201, $res1->getStatusCode());

        // second identical request with same key -> should replay stored response and NOT double-charge
        $req2 = $this->createRequest('POST', '/transacao', $headers);
        $req2->getBody()->write(json_encode($tx));
        $req2->getBody()->rewind();
        $res2 = $app->handle($req2);

        $this->assertEquals(201, $res2->getStatusCode());
        $this->assertEquals((string)$res1->getBody(), (string)$res2->getBody());

        // check repository balance only decreased once (100 -> 90)
        $container = $app->getContainer();
        $repo = $container->get(\App\Domain\Account\AccountRepository::class);
        $this->assertEquals(90.0, $repo->findAccountOfNumber(777)->getSaldo());
    }

    public function testSameKeyDifferentBodyReturns409()
    {
        $app = $this->getAppInstance();

        // create account
        $req = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req->getBody()->write(json_encode(['numero_conta' => 888, 'saldo' => 100]));
        $req->getBody()->rewind();
        $app->handle($req);

        $txA = ['forma_pagamento' => 'P', 'numero_conta' => 888, 'valor' => 10];
        $txB = ['forma_pagamento' => 'P', 'numero_conta' => 888, 'valor' => 20];
        $headers = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json', 'Idempotency-Key' => 'idem-xyz'];

        $req1 = $this->createRequest('POST', '/transacao', $headers);
        $req1->getBody()->write(json_encode($txA));
        $req1->getBody()->rewind();
        $res1 = $app->handle($req1);
        $this->assertEquals(201, $res1->getStatusCode());

        $req2 = $this->createRequest('POST', '/transacao', $headers);
        $req2->getBody()->write(json_encode($txB));
        $req2->getBody()->rewind();
        $res2 = $app->handle($req2);

        $this->assertEquals(409, $res2->getStatusCode());
    }
}