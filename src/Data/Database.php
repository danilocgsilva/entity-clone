<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone\Data;

class Database
{
    /** @var Table[] */
    private array $tables = [];

    public function __construct(private string $name) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function addTable(Table $table): self
    {
        $this->tables[] = $table;
        return $this;
    }

    /** @return Table[] */
    public function getTables(): array
    {
        return $this->tables;
    }
}
