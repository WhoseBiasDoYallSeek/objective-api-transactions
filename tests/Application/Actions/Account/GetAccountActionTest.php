<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Account;

use Tests\TestCase;

class GetAccountActionTest extends TestCase
{
    public function testGetAccountNotFound()
    {
        $app = $this->getAppInstance();

        $request = $this->createRequest('GET', '/conta');
        $request = $request->withQueryParams(['numero_conta' => 999]);
        $response = $app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetAccountFound()
    {
        $app = $this->getAppInstance();

        // create
        $data = ['numero_conta' => 234, 'saldo' => 180.37];
        $req = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req->getBody()->write(json_encode($data));
        $req->getBody()->rewind();
        $app->handle($req);

        $request = $this->createRequest('GET', '/conta');
        $request = $request->withQueryParams(['numero_conta' => 234]);
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('"numero_conta": 234', (string) $response->getBody());
    }
}
