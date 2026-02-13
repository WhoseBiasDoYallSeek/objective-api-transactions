<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Infrastructure\Persistence\Idempotency\SqliteIdempotencyRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class IdempotencyPruneTest extends TestCase
{
    public function testPruneExpiredDeletesOldRows(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE idempotency (idempotency_key TEXT PRIMARY KEY, request_hash TEXT NOT NULL, response_code INTEGER NOT NULL, response_body TEXT NOT NULL, created_at TEXT NOT NULL)');

        // insert one old row and one recent row
        $pdo->exec("INSERT INTO idempotency (idempotency_key, request_hash, response_code, response_body, created_at) VALUES ('old-key', 'h', 200, 'old', '2000-01-01 00:00:00')");
        $pdo->exec("INSERT INTO idempotency (idempotency_key, request_hash, response_code, response_body, created_at) VALUES ('new-key', 'h2', 200, 'new', datetime('now'))");

        $repo = new SqliteIdempotencyRepository($pdo);

        // prune entries older than 1 day (86400s) — should remove the old one
        $deleted = $repo->pruneExpired(86400);
        $this->assertSame(1, $deleted);

        $this->assertNull($repo->find('old-key'));
        $this->assertNotNull($repo->find('new-key'));
    }
}
