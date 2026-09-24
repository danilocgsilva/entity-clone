<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone;

use PDO;
use PDOException;

class Helpers
{
    /**
     * Checks if a database exists in the given PDO connection
     *
     * @param PDO $pdo
     * @param string $databaseName
     * @return bool
     */
    public static function databaseExists(PDO $pdo, string $databaseName): bool
    {
        try {
            $sql = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :database_name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':database_name' => $databaseName]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
}
