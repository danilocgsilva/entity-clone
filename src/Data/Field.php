<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone\Data;

class Field
{
    public readonly string $name;
    public readonly string $type;
    public readonly bool $null;
    public readonly string $key;
    public readonly ?string $default;
    public readonly string $extra;
    public readonly string $comment;
    public readonly ?string $collation;
    public readonly string $privileges;

    public static function fromRow(array $row): self
    {
        $field = new self();
        $field->name = $row['Field'];
        $field->type = $row['Type'];
        $field->null = $row['Null'] === 'YES';
        $field->key = $row['Key'];
        $field->default = $row['Default'];
        $field->extra = $row['Extra'];
        $field->comment = $row['Comment'];
        $field->collation = $row['Collation'];
        $field->privileges = $row['Privileges'];

        return $field;
    }
}
