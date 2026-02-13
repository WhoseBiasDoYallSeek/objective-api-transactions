<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Account;

use Tests\TestCase;

class CreateAccountActionTest extends TestCase
{
    public function testCreateAccount()
    {
        $app = $this->getAppInstance();

        $data = ['numero_conta' => 234, 'saldo' => 180.37];

        $request = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $request->getBody()->write(json_encode($data));
        $request->getBody()->rewind();

        $response = $app->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('"numero_conta": 234', $body);
        $this->assertStringContainsString('"saldo": 180.37', $body);
    }

    public function testCreateDuplicateAccountReturns400()
    {
        $app = $this->getAppInstance();

        $data = ['numero_conta' => 234, 'saldo' => 180.37];

        $request = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $request->getBody()->write(json_encode($data));
        $request->getBody()->rewind();
        $app->handle($request);

        // second attempt
        $request2 = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $request2->getBody()->write(json_encode($data));
        $request2->getBody()->rewind();
        $response2 = $app->handle($request2);

        $this->assertEquals(400, $response2->getStatusCode());
    }
}
