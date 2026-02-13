<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

return function (ContainerBuilder $containerBuilder) {
    // Choose repository implementation based on settings (default: in-memory)
    $containerBuilder->addDefinitions([
        // Account repository
        \App\Domain\Account\AccountRepository::class => function (ContainerInterface $c) {
            // prefer in-memory for unit tests
            if (defined('PHPUNIT_COMPOSER_INSTALL')) {
                return new \App\Infrastructure\Persistence\Account\InMemoryAccountRepository();
            }

            $settings = $c->get(SettingsInterface::class);
            $driver = $settings->get('db')['driver'] ?? 'memory';

            if ($driver === 'sqlite') {
                $dbPath = $settings->get('db')['database'] ?? null;
                // use sqlite only if DB file exists and appears initialized (non-empty)
                if ($dbPath && file_exists($dbPath) && filesize($dbPath) > 0) {
                    return new \App\Infrastructure\Persistence\Account\SqliteAccountRepository($c->get(\PDO::class));
                }
            }

            return new \App\Infrastructure\Persistence\Account\InMemoryAccountRepository();
        },

        // Idempotency repository (stores responses for retry safety)
        \App\Domain\Idempotency\IdempotencyRepository::class => function (ContainerInterface $c) {
            // Prefer in-memory repository during PHPUnit to keep tests deterministic
            if (defined('PHPUNIT_COMPOSER_INSTALL')) {
                return new class implements \App\Domain\Idempotency\IdempotencyRepository {
                    private array $store = [];
                    public function find(string $key): ?array { return $this->store[$key] ?? null; }
                    public function save(string $key, string $requestHash, int $responseCode, string $responseBody): void { $this->store[$key] = ['request_hash' => $requestHash, 'response_code' => $responseCode, 'response_body' => $responseBody, 'created_at' => date('c')]; }
                    public function delete(string $key): void { unset($this->store[$key]); }
                    public function pruneExpired(int $ttlSeconds): int {
                        $now = time();
                        $deleted = 0;
                        foreach (array_keys($this->store) as $k) {
                            $created = strtotime($this->store[$k]['created_at']);
                            if ($created < ($now - $ttlSeconds)) {
                                unset($this->store[$k]);
                                $deleted++;
                            }
                        }
                        return $deleted;
                    }
                };
            }

            $settings = $c->get(SettingsInterface::class);
            $driver = $settings->get('db')['driver'] ?? 'memory';

            if ($driver === 'sqlite') {
                return new \App\Infrastructure\Persistence\Idempotency\SqliteIdempotencyRepository($c->get(\PDO::class));
            }

            // fallback: simple in-memory implementation for environments without sqlite
            return new class implements \App\Domain\Idempotency\IdempotencyRepository {
                private array $store = [];
                public function find(string $key): ?array { return $this->store[$key] ?? null; }
                public function save(string $key, string $requestHash, int $responseCode, string $responseBody): void { $this->store[$key] = ['request_hash' => $requestHash, 'response_code' => $responseCode, 'response_body' => $responseBody, 'created_at' => date('c')]; }
                public function delete(string $key): void { unset($this->store[$key]); }
                public function pruneExpired(int $ttlSeconds): int {
                    $now = time();
                    $deleted = 0;
                    foreach (array_keys($this->store) as $k) {
                        $created = strtotime($this->store[$k]['created_at']);
                        if ($created < ($now - $ttlSeconds)) {
                            unset($this->store[$k]);
                            $deleted++;
                        }
                    }
                    return $deleted;
                }
            };
        },
    ]);
};
