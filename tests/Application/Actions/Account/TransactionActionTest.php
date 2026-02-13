<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Account;

use Tests\TestCase;

class TransactionActionTest extends TestCase
{
    public function testDebitTransactionReducesBalanceWithFee()
    {
        $app = $this->getAppInstance();

        // create account with 180.37
        $data = ['numero_conta' => 234, 'saldo' => 180.37];
        $req = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req->getBody()->write(json_encode($data));
        $req->getBody()->rewind();
        $app->handle($req);

        // debit 10 (3% fee -> 10.30)
        $tx = ['forma_pagamento' => 'D', 'numero_conta' => 234, 'valor' => 10];
        $req2 = $this->createRequest('POST', '/transacao', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req2->getBody()->write(json_encode($tx));
        $req2->getBody()->rewind();

        $res = $app->handle($req2);
        $this->assertEquals(201, $res->getStatusCode());
        $this->assertStringContainsString('"saldo": 170.07', (string) $res->getBody());
    }

    public function testTransactionFailsWhenInsufficientFunds()
    {
        $app = $this->getAppInstance();

        // create account with small balance
        $data = ['numero_conta' => 999, 'saldo' => 5.00];
        $req = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req->getBody()->write(json_encode($data));
        $req->getBody()->rewind();
        $app->handle($req);

        // attempt debit larger than balance
        $tx = ['forma_pagamento' => 'D', 'numero_conta' => 999, 'valor' => 10];
        $req2 = $this->createRequest('POST', '/transacao', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req2->getBody()->write(json_encode($tx));

        $res = $app->handle($req2);
        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testPixHasNoFee()
    {
        $app = $this->getAppInstance();

        // create account directly in repository to avoid relying on HTTP create route in this unit test
        $container = $app->getContainer();
        $accountRepo = $container->get(\App\Domain\Account\AccountRepository::class);
        $accountRepo->add(new \App\Domain\Account\Account(555, 100.00));
        // sanity check: repository contains the account
        $this->assertEquals(100.00, $accountRepo->findAccountOfNumber(555)->getSaldo());

        $tx = ['forma_pagamento' => 'P', 'numero_conta' => 555, 'valor' => 10];
        $req2 = $this->createRequest('POST', '/transacao', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req2->getBody()->write(json_encode($tx));
        $req2->getBody()->rewind();

        $res = $app->handle($req2);
        $this->assertEquals(201, $res->getStatusCode());
        $this->assertStringContainsString('"saldo": 90', (string) $res->getBody());
    }

    public function testTransactionReturns400ForMissingOrInvalidPayload()
    {
        $app = $this->getAppInstance();

        // Missing fields -> should return 400 + detailed validation error
        $req = $this->createRequest('POST', '/transacao', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req->getBody()->write(json_encode([]));
        $req->getBody()->rewind();
        $res = $app->handle($req);
        $this->assertEquals(400, $res->getStatusCode());
        $this->assertStringContainsString('forma_pagamento is required', (string)$res->getBody());

        // Invalid forma_pagamento -> 400 + enum error
        $req2 = $this->createRequest('POST', '/transacao', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req2->getBody()->write(json_encode(['forma_pagamento' => 'X', 'numero_conta' => 1, 'valor' => 10]));
        $req2->getBody()->rewind();
        $res2 = $app->handle($req2);
        $this->assertEquals(400, $res2->getStatusCode());
        $this->assertStringContainsString('forma_pagamento has invalid value', (string)$res2->getBody());

        // Non-integer numero_conta / negative valor -> 400 + type/minimum errors
        $req3 = $this->createRequest('POST', '/transacao', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req3->getBody()->write(json_encode(['forma_pagamento' => 'D', 'numero_conta' => 'abc', 'valor' => -5]));
        $req3->getBody()->rewind();
        $res3 = $app->handle($req3);
        $this->assertEquals(400, $res3->getStatusCode());
        $this->assertStringContainsString('numero_conta must be of type integer', (string)$res3->getBody());
        $this->assertStringContainsString('valor must be >= 0.01', (string)$res3->getBody());
    }}
