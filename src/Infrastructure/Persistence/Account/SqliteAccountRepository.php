<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Account;

use App\Domain\Account\Account;
use App\Domain\Account\AccountNotFoundException;
use App\Domain\Account\AccountRepository;
use PDO;

class SqliteAccountRepository implements AccountRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(Account $account): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO accounts (numero_conta, saldo) VALUES (:numero_conta, :saldo)');
        $stmt->execute([
            'numero_conta' => $account->getNumeroConta(),
            'saldo' => $account->getSaldo(),
        ]);
    }

    public function findAccountOfNumber(int $numeroConta): Account
    {
        $stmt = $this->pdo->prepare('SELECT numero_conta, saldo FROM accounts WHERE numero_conta = :numero_conta');
        $stmt->execute(['numero_conta' => $numeroConta]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new AccountNotFoundException();
        }

        return new Account((int)$row['numero_conta'], (float)$row['saldo']);
    }

    public function update(Account $account): void
    {
        $stmt = $this->pdo->prepare('UPDATE accounts SET saldo = :saldo WHERE numero_conta = :numero_conta');
        $stmt->execute([
            'saldo' => $account->getSaldo(),
            'numero_conta' => $account->getNumeroConta(),
        ]);
    }
}
