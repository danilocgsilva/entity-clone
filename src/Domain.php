<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone;

use Danilocgsilva\EntityClone\Data\Field;
use PDO;
use Danilocgsilva\EntityClone\Entities\DatabaseAccess;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

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
}
