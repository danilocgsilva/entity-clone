<?php

declare(strict_types=1);

namespace Danilocgsilva\GroceriesMemory;

interface EntityManagerConfigInterface
{
    public function getHost(): string;
    public function getPort(): int;
    public function getDatabaseName(): string;
    public function getUsername(): string;
    public function getPassword(): string;
    public function getEnvironment(): string;
}
