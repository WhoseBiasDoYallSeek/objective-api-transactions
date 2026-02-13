<?php

declare(strict_types=1);

namespace App\Domain\Account;

interface AccountRepository
{
    public function add(Account $account): void;

    public function findAccountOfNumber(int $numeroConta): Account;

    public function update(Account $account): void;
}
