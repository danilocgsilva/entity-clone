<?php

declare(strict_types=1);

namespace Danilocgsilva\GroceriesMemory;


class DefaultEntityManagerConfig implements EntityManagerConfigInterface
{
    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 3306,
        private readonly string $databaseName = '',
        private readonly string $username = '',
        private readonly string $password = '',
        private readonly string $environment = 'dev'
    ) {
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }
}
