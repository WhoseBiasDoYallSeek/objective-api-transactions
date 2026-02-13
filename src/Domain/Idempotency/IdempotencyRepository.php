<?php

declare(strict_types=1);

namespace App\Domain\Idempotency;

interface IdempotencyRepository
{
    /**
     * Find stored response for an idempotency key.
     * Returns null when not found.
     *
     * @param string $key
     * @return array<string,mixed>|null ['request_hash' => string, 'response_code' => int, 'response_body' => string]
     */
    public function find(string $key): ?array;

    /**
     * Persist response for a given key.
     *
     * @param string $key
     * @param string $requestHash
     * @param int $responseCode
     * @param string $responseBody
     */
    public function save(string $key, string $requestHash, int $responseCode, string $responseBody): void;

    /**
     * Delete an idempotency entry (used for TTL/cleanup).
     */
    public function delete(string $key): void;

    /**
     * Remove idempotency entries older than the given TTL (in seconds).
     * Returns the number of entries removed.
     *
     * @param int $ttlSeconds
     * @return int
     */
    public function pruneExpired(int $ttlSeconds): int;
}

