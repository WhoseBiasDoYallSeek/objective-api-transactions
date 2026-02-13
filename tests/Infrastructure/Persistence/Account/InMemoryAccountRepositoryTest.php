<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Account;

use App\Domain\Account\Account;
use App\Infrastructure\Persistence\Account\InMemoryAccountRepository;
use Tests\TestCase;

class InMemoryAccountRepositoryTest extends TestCase
{
    public function testAddAndFindAndUpdate()
    {
        $repo = new InMemoryAccountRepository();

        $acc = new Account(234, 180.37);
        $repo->add($acc);

        $f = $repo->findAccountOfNumber(234);
        $this->assertEquals(234, $f->getNumeroConta());
        $this->assertEquals(180.37, $f->getSaldo());

        $f->setSaldo(100.50);
        $repo->update($f);

        $f2 = $repo->findAccountOfNumber(234);
        $this->assertEquals(100.50, $f2->getSaldo());
    }

    public function testFindThrowsWhenNotFound()
    {
        $repo = new InMemoryAccountRepository();
        $this->expectException(\App\Domain\Account\AccountNotFoundException::class);
        $repo->findAccountOfNumber(999);
    }
}
