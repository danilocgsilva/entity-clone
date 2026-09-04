<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone;

use Danilocgsilva\EntityClone\Entities\DatabaseAccess;
use PDO;

class DatabaseWorks
{
    private PDO $pdo;

    public function __construct(DatabaseAccess $databaseAccess)
    {
        $dsn = "mysql:host={$databaseAccess->getHost()};port={$databaseAccess->getPort()}";
        $this->pdo = new PDO(
            $dsn,
            $databaseAccess->getUser(),
            $databaseAccess->getPassword(),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    /**
     * @return string[]
     */
    public function listDatabasesNames(): array
    {
        return $this->pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    }
}
