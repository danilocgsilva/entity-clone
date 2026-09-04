<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone\Data;

class Table
{
    /** @var Field[] */
    private array $fields = [];

    public function __construct(private string $name) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function addField(Field $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    /** @return Field[] */
    public function getFields(): array
    {
        return $this->fields;
    }
}
