<?php

declare(strict_types=1);

namespace App\Application\Actions\Account;

use App\Domain\Account\Account;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class CreateAccountAction extends AccountAction
{
    protected function action(): Response
    {
        $data = (array) $this->getFormData();

        $numero = (int) ($data['numero_conta'] ?? 0);
        $saldo = (float) ($data['saldo'] ?? 0.0);

        if ($numero <= 0) {
            throw new HttpBadRequestException($this->request, 'Número de conta inválido');
        }

        $account = new Account($numero, $saldo);

        try {
            $this->accountRepository->add($account);
        } catch (\InvalidArgumentException $e) {
            throw new HttpBadRequestException($this->request, $e->getMessage());
        }

        return $this->respondWithData($account, 201);
    }
}
