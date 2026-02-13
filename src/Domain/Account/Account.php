<?php

declare(strict_types=1);

namespace App\Domain\Account;

use JsonSerializable;

class Account implements JsonSerializable
{
    private int $numeroConta;

    private float $saldo;

    public function __construct(int $numeroConta, float $saldo)
    {
        $this->numeroConta = $numeroConta;
        $this->saldo = $saldo;
    }

    public function getNumeroConta(): int
    {
        return $this->numeroConta;
    }

    public function getSaldo(): float
    {
        return $this->saldo;
    }

    public function setSaldo(float $saldo): void
    {
        $this->saldo = $saldo;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'numero_conta' => $this->numeroConta,
            'saldo' => round($this->saldo, 2),
        ];
    }
}
