<?php

declare(strict_types=1);

namespace App\Application\Actions\Account;

use App\Domain\Account\Account;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class TransactionAction extends AccountAction
{
    protected function action(): Response
    {
        $data = (array) $this->getFormData();

        // JSON-schema (small subset) for transaction payload
        $schema = [
            'required' => ['forma_pagamento', 'numero_conta', 'valor'],
            'properties' => [
                'forma_pagamento' => ['type' => 'string', 'enum' => ['P', 'C', 'D']],
                'numero_conta' => ['type' => 'integer', 'minimum' => 1],
                'valor' => ['type' => 'number', 'minimum' => 0.01],
            ],
        ];

        $validator = new \App\Application\Validation\JsonSchemaValidator();
        $errors = $validator->validate($data, $schema);

        if (!empty($errors)) {
            // return detailed validation error payload
            $errorDescription = implode('; ', array_map(fn($k, $v) => "{$k} {$v}", array_keys($errors), $errors));
            return $this->respond(new \App\Application\Actions\ActionPayload(400, null, new \App\Application\Actions\ActionError(\App\Application\Actions\ActionError::VALIDATION_ERROR, $errorDescription)));
        }

        $forma = $data['forma_pagamento'];
        $numero = (int) $data['numero_conta'];
        $valor = (float) $data['valor'];

        $account = $this->accountRepository->findAccountOfNumber($numero);

        // taxas: D=3%, C=5%, P=0%
        $feeRate = match ($forma) {
            'D' => 0.03,
            'C' => 0.05,
            'P' => 0.0,
        };

        $deduction = $valor * (1 + $feeRate);
        $newSaldo = round($account->getSaldo() - $deduction, 2);

        if ($newSaldo < 0) {
            // Retorna 404 para indicar saldo insuficiente, já que a conta existe mas não tem fundos suficientes para a transação
            throw new HttpNotFoundException($this->request, 'Saldo insuficiente');
        }

        $account->setSaldo($newSaldo);
        $this->accountRepository->update($account);

        return $this->respondWithData($account, 201);
    }
}
