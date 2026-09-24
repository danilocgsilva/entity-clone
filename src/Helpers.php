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

    /**
     * Checks if a table exists in the given database and PDO connection
     *
     * @param PDO $pdo
     * @param string $databaseName
     * @param string $tableName
     * @return bool
     */
    public static function tableExists(PDO $pdo, string $databaseName, string $tableName): bool
    {
        try {
            $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_SCHEMA = :database_name 
                    AND TABLE_NAME = :table_name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':database_name' => $databaseName,
                ':table_name' => $tableName
            ]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
}
