<?php

declare(strict_types=1);

namespace App\Application\Actions\Account;

use Psr\Http\Message\ResponseInterface as Response;

class GetAccountAction extends AccountAction
{
    protected function action(): Response
    {
        $query = $this->request->getQueryParams();
        $numero = (int) ($query['numero_conta'] ?? 0);

        $account = $this->accountRepository->findAccountOfNumber($numero);

        return $this->respondWithData($account, 200);
    }
}
