<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Account;

use App\Domain\Account\Account;
use App\Domain\Account\AccountNotFoundException;
use App\Domain\Account\AccountRepository;

class InMemoryAccountRepository implements AccountRepository
{
    /** @var Account[] */
    private array $accounts;

    public function __construct(array $accounts = null)
    {
        $this->accounts = $accounts ?? [];
    }

    public function add(Account $account): void
    {
        $num = $account->getNumeroConta();
        if (isset($this->accounts[$num])) {
            throw new \InvalidArgumentException('Account already exists.');
        }

        $this->accounts[$num] = $account;
    }

    public function findAccountOfNumber(int $numeroConta): Account
    {
        if (!isset($this->accounts[$numeroConta])) {
            throw new AccountNotFoundException();
        }

        return $this->accounts[$numeroConta];
    }

    public function update(Account $account): void
    {
        $num = $account->getNumeroConta();
        if (!isset($this->accounts[$num])) {
            throw new AccountNotFoundException();
        }

        $this->accounts[$num] = $account;
    }
}
