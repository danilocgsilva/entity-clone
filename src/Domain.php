<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone;

use Danilocgsilva\EntityClone\Data\Field;
use PDO;
use Danilocgsilva\EntityClone\Entities\DatabaseAccess;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Exception;
use PDOException;

class Domain
{
    /**
     * @return Field[]
     */
    public static function getFieldsFromTable(PDO $pdo, string $tableName): array
    {
        $sql = "SHOW FULL COLUMNS FROM {$tableName}";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        return array_map(fn($row) => Field::fromRow($row), $sth->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return PDO
     */
    public static function getPdoFromDatabaseAccessId(int $id, EntityManagerInterface $entityManager): PDO
    {
        $repository = $entityManager->getRepository(DatabaseAccess::class);
        $databaseAccess = $repository->find($id);
        
        if (!$databaseAccess) {
            throw new RuntimeException("DatabaseAccess with id {$id} not found");
        }
        
        $dsn = "mysql:host={$databaseAccess->getHost()};port={$databaseAccess->getPort()};dbname={$databaseAccess->getDatabaseName()}";
        
        return new PDO(
            $dsn,
            $databaseAccess->getUser(),
            $databaseAccess->getPassword(),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public static function cloneRecordSecure(PDO $sourcePdo, PDO $targetPdo, string $tableName, int $id): bool
    {
        try {
            // Validate inputs
            if (empty($tableName)) {
                throw new Exception("Table name cannot be empty");
            }
            
            // Get column information from source table
            $columnsSql = "SHOW COLUMNS FROM `$tableName`";
            $columnsStmt = $sourcePdo->query($columnsSql);
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($columns)) {
                throw new Exception("No columns found in table {$tableName}");
            }
            
            // Get the record
            $selectSql = "SELECT * FROM `$tableName` WHERE id = :id LIMIT 1";
            $selectStmt = $sourcePdo->prepare($selectSql);
            $selectStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $selectStmt->execute();
            
            $record = $selectStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                throw new Exception("Record with ID {$id} not found in table {$tableName}");
            }
            
            // Prepare insert query
            $columnNames = array_diff($columns, ['id']); // Remove 'id' to let auto-increment work
            $placeholders = implode(', ', array_fill(0, count($columnNames), '?'));
            
            $insertSql = "INSERT INTO `$tableName` (" . implode(', ', $columnNames) . ") VALUES ($placeholders)";
            $insertStmt = $targetPdo->prepare($insertSql);
            
            // Get values for insertion
            $insertValues = [];
            foreach ($columnNames as $columnName) {
                $insertValues[] = $record[$columnName];
            }
            
            // Execute the insert
            $result = $insertStmt->execute($insertValues);
            
            if ($result) {
                return true;
            } else {
                throw new Exception("Failed to insert record into target database");
            }
            
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Error cloning record: " . $e->getMessage());
        }
    }

    /**
     * @return string[]
     */
    public static function listTables(PDO $pdo, string $databaseName): array
    {
        try {
            // Switch to the specified database
            $pdo->exec("USE `$databaseName`");
            
            $sql = "SHOW TABLES";
            $stmt = $pdo->query($sql);
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Convert to string array
            return array_map('strval', $tables);
        } catch (PDOException $e) {
            throw new Exception("Failed to list tables: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Error listing tables: " . $e->getMessage());
        }
    }

    /**
     * Prints the SHOW CREATE TABLE statement for a given table
     *
     * @param PDO $pdo
     * @param string $tableName
     * @return string
     */
    public static function printCreateTable(PDO $pdo, string $tableName): string
    {
        try {
            $sql = "SHOW CREATE TABLE `$tableName`";
            $stmt = $pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['Create Table'])) {
                return $result['Create Table'] . ";\n";
            } else {
                throw new Exception("Could not retrieve CREATE TABLE statement for table {$tableName}");
            }
        } catch (PDOException $e) {
            throw new Exception("Database error while retrieving CREATE TABLE statement: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Error retrieving CREATE TABLE statement: " . $e->getMessage());
        }
    }

    /**
     * Tests if the PDO connection is working properly
     *
     * @param PDO $pdo
     * @return bool
     * @throws Exception
     */
    public static function testPdoConnection(PDO $pdo): bool
    {
        try {
            $pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            throw new Exception("PDO connection test failed: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Error testing PDO connection: " . $e->getMessage());
        }
    }

    public static function getTableForeignKeys(PDO $pdo, string $databaseName, string $tableName): array
    {
        $sql = "
            SELECT 
                k.COLUMN_NAME as column_name,
                k.REFERENCED_TABLE_NAME as referenced_table_name,
                k.REFERENCED_COLUMN_NAME as referenced_column_name
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
            WHERE k.TABLE_SCHEMA = :database_name 
            AND k.TABLE_NAME = :table_name
            AND k.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY k.ORDINAL_POSITION";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':database_name' => $databaseName,
            ':table_name' => $tableName
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
