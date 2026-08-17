<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone;

use Danilocgsilva\EntityClone\Data\Field;
use PDO;

class Domain
{
    /** @return Field[] */
    public static function getFieldsFromTable(PDO $pdo, string $tableName): array
    {
        $sql = "SHOW FULL COLUMNS FROM {$tableName}";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        return array_map(fn($row) => Field::fromRow($row), $sth->fetchAll(PDO::FETCH_ASSOC));
    }
}
