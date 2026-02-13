<?php

declare(strict_types=1);

namespace Tests\Application\Middleware;

use Tests\TestCase;

class IdempotencyTTLTest extends TestCase
{
    public function testExpiredEntryIsIgnoredAndRequestProcessedAgain()
    {
        $app = $this->getAppInstance();

        // create account
        $req = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req->getBody()->write(json_encode(['numero_conta' => 2000, 'saldo' => 100]));
        $req->getBody()->rewind();
        $app->handle($req);

        $txA = ['forma_pagamento' => 'P', 'numero_conta' => 2000, 'valor' => 10];
        $headers = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json', 'Idempotency-Key' => 'ttl-key'];

        // first request — stored
        $r1 = $this->createRequest('POST', '/transacao', $headers);
        $r1->getBody()->write(json_encode($txA));
        $r1->getBody()->rewind();
        $res1 = $app->handle($r1);
        $this->assertEquals(201, $res1->getStatusCode());

        // manipulate in-memory idempotency store to mark entry as old (expiry)
        $repo = $app->getContainer()->get(\App\Domain\Idempotency\IdempotencyRepository::class);
        $rp = new \ReflectionObject($repo);
        $prop = $rp->getProperty('store');
        $prop->setAccessible(true);
        $store = $prop->getValue($repo);

        // set created_at older than default TTL (24h)
        $store['ttl-key']['created_at'] = date('c', time() - 90000);
        $prop->setValue($repo, $store);

        // send a different transaction with same key -> should be processed (not 409)
        $txB = ['forma_pagamento' => 'P', 'numero_conta' => 2000, 'valor' => 20];
        $r2 = $this->createRequest('POST', '/transacao', $headers);
        $r2->getBody()->write(json_encode($txB));
        $r2->getBody()->rewind();
        $res2 = $app->handle($r2);

        $this->assertEquals(201, $res2->getStatusCode());

        // balance should have been reduced twice (100 -> 90 -> 70)
        $accountRepo = $app->getContainer()->get(\App\Domain\Account\AccountRepository::class);
        $this->assertEquals(70.0, $accountRepo->findAccountOfNumber(2000)->getSaldo());
    }
}