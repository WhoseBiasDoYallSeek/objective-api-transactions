# Objective API — Transactions (Slim 4)

This repository is a small tecnical challenge project implementing a banking API (accounts + transactions) with idempotency and tests.

Este repositório é um pequeno projeto para desafio técnico que implementa uma API bancária (contas + transações) com idempotência e testes.

---

## Quick start / Execução rápida

English
- Run tests: `composer test`
- Start local server: `composer start` (php built-in server)
- Docker (build & run):
  - `docker build -t obj-api-transactions .`
  - `docker run --rm -p 8080:8080 obj-api-transactions`
- Docker Compose (recommended): `docker-compose up --build -d`

Português
- Rodar testes: `composer test`
- Iniciar servidor local: `composer start` (servidor embutido do PHP)
- Docker (build & run):
  - `docker build -t obj-api-transactions .`
  - `docker run --rm -p 8080:8080 obj-api-transactions`
- Docker Compose (recomendado): `docker-compose up --build -d`

The container includes a lightweight cron that runs `scripts/prune_idempotency.php` hourly (removes expired idempotency entries). Logs are written to `logs/prune.log`.

O container inclui um cron leve que executa `scripts/prune_idempotency.php` a cada hora (remove entradas de idempotência expiradas). Logs em `logs/prune.log`.

---

## API — quick reference (EN / PT-BR)

It's necessary to use Accept: application/json HTTP Header to run the endpoints.
É necessário usar o HTTP Header Accept: application/json para rodar os endpoints.

The application only accept json requests.
A aplicação aceita somente requests json.

Endpoints / Endpoints

- `POST /conta` — Create account / Criar conta
  - Response: `{ "id": string, "saldo": number }`

- `GET /conta` — Get account / Obter conta

- `POST /transacao` — Create transaction / Criar transação
  - Request JSON / JSON de requisição: `{ "tipo": "D|C|P", "valor": <number> }`
    - D = Debit / Débito — fee 3% / taxa 3%
    - C = Credit / Crédito — fee 5% / taxa 5%
    - P = Pix (instant) — fee 0% / taxa 0%
  - Idempotency: send header `Idempotency-Key: <random>` to prevent duplicate processing. Replays return same 2xx response; same key + different payload → 409.

---

## OpenAPI / Contrato

A small `openapi.yaml` is included in the repository root. Use it to import to Postman/Insomnia or generate documentation.

Um `openapi.yaml` pequeno está no root do repositório. Use para importar no Postman/Insomnia ou gerar documentação.

---

## Scripts & utilities / Scripts & utilitários

- `scripts/seed_database.php` — seed inicial do banco (runtime sqlite)
- `scripts/prune_idempotency.php [TTL_SECONDS]` — remove entradas expirada (TTL opcional)
- `scripts/debug_idempotency.php` — script de debug local para demonstrar idempotência

---

## Demo curl examples (EN / PT-BR)

Create account / Criar conta

```bash
curl -s -X POST http://localhost:8080/conta | jq
```

Idempotent transaction / Transação idempotente

```bash
curl -s -X POST http://localhost:8080/transacao \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: my-key-123" \
  -d '{"tipo":"D","valor":10}' | jq
```

---

## Notes / Observações

- The project uses in-memory repositories during PHPUnit for fast deterministic tests; runtime uses SQLite.  
- Tests and important logic live in `src/Application/Actions` and `src/Application/Middleware`.

- O projeto usa repositórios em memória durante o PHPUnit para testes rápidos e determinísticos; em runtime utiliza SQLite.  
- Testes e lógica principal estão em `src/Application/Actions` e `src/Application/Middleware`.

---
