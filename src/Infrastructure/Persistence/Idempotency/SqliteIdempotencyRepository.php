<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Idempotency;

use App\Domain\Idempotency\IdempotencyRepository;
use PDO;

class SqliteIdempotencyRepository implements IdempotencyRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find(string $key): ?array
    {
        $stmt = $this->pdo->prepare('SELECT request_hash, response_code, response_body, created_at FROM idempotency WHERE idempotency_key = :k');
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'request_hash' => $row['request_hash'],
            'response_code' => (int) $row['response_code'],
            'response_body' => $row['response_body'],
            'created_at' => $row['created_at'],
        ];
    }

    public function save(string $key, string $requestHash, int $responseCode, string $responseBody): void
    {
        $stmt = $this->pdo->prepare('INSERT OR REPLACE INTO idempotency (idempotency_key, request_hash, response_code, response_body, created_at) VALUES (:k, :rh, :rc, :rb, CURRENT_TIMESTAMP)');
        $stmt->execute([
            'k' => $key,
            'rh' => $requestHash,
            'rc' => $responseCode,
            'rb' => $responseBody,
        ]);
    }

    public function delete(string $key): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM idempotency WHERE idempotency_key = :k');
        $stmt->execute(['k' => $key]);
    }

    public function pruneExpired(int $ttlSeconds): int
    {
        // compute threshold in PHP to avoid sqlite datetime parsing edge-cases
        $threshold = date('Y-m-d H:i:s', time() - $ttlSeconds);
        $stmt = $this->pdo->prepare('DELETE FROM idempotency WHERE created_at < :threshold');
        $stmt->execute(['threshold' => $threshold]);

        return $stmt->rowCount();
    }
}

