<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone;

class Helpers
{
    /**
     * Checks if a database exists in the given PDO connection
     *
     * @param PDO $pdo
     * @param string $databaseName
     * @return bool
     */
    private static function databaseExists(PDO $pdo, string $databaseName): bool
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
     * Cleans the CREATE TABLE statement by removing the table name part
     *
     * @param string $createTableStatement
     * @param string $tableName
     * @return string
     */
    private static function cleanCreateTableStatement(string $createTableStatement, string $tableName): string
    {
        // Remove the "CREATE TABLE `table_name`" part from the statement
        // This assumes the statement starts with "CREATE TABLE `tableName`"
        $pattern = "/^CREATE TABLE `{$tableName}` /i";
        $cleanedStatement = preg_replace($pattern, 'CREATE TABLE ', $createTableStatement);
        
        // Also remove any trailing semicolon that might be left
        $cleanedStatement = rtrim(trim($cleanedStatement), ';');
        
        return $cleanedStatement;
    }
}
