<?php

declare(strict_types=1);

namespace Tests\Application\Middleware;

use Tests\TestCase;

class JsonOnlyMiddlewareTest extends TestCase
{
    public function testGetWithoutAcceptJsonReturns406()
    {
        $app = $this->getAppInstance();

        $req = $this->createRequest('GET', '/conta', ['HTTP_ACCEPT' => 'text/plain']);
        $res = $app->handle($req);

        $this->assertEquals(406, $res->getStatusCode());
        $this->assertStringContainsString('Only application/json responses are supported', (string)$res->getBody());
    }

    public function testPostWithoutContentTypeReturns415()
    {
        $app = $this->getAppInstance();

        $req = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json']);
        // no CONTENT_TYPE header -> should be rejected
        $res = $app->handle($req->withParsedBody(['numero_conta' => 1, 'saldo' => 100]));

        $this->assertEquals(415, $res->getStatusCode());
        $this->assertStringContainsString('Only application/json content is accepted', (string)$res->getBody());
    }

    public function testPostWithWrongContentTypeReturns415()
    {
        $app = $this->getAppInstance();

        $req = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'text/plain']);
        $res = $app->handle($req->withParsedBody(['numero_conta' => 2, 'saldo' => 50]));

        $this->assertEquals(415, $res->getStatusCode());
        $this->assertStringContainsString('Only application/json content is accepted', (string)$res->getBody());
    }

    public function testGetWithAcceptJsonPasses()
    {
        $app = $this->getAppInstance();

        $req = $this->createRequest('GET', '/conta', ['HTTP_ACCEPT' => 'application/json']);
        $res = $app->handle($req);

        $this->assertNotEquals(406, $res->getStatusCode());
    }

    public function testPostWithJsonHeadersPasses()
    {
        $app = $this->getAppInstance();

        $req = $this->createRequest('POST', '/conta', ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']);
        $req = $req->withParsedBody(['numero_conta' => 3, 'saldo' => 10]);
        $res = $app->handle($req);

        $this->assertNotEquals(415, $res->getStatusCode());
    }
}
