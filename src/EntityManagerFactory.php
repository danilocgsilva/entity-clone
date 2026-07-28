<?php
declare(strict_types=1);

namespace Danilocgsilva\EntityClone;

use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\DriverManager;
use Dotenv\Dotenv;
use RuntimeException;

final class EntityManagerFactory
{
    public static function create(string $projectRoot, array $entityPaths): EntityManagerInterface
    {
        if (file_exists($projectRoot . '/.env')) {
            Dotenv::createImmutable($projectRoot)->load();
        }

        $env = getenv('APP_ENV') ?: 'dev';
        $isDevMode = $env !== 'prod';

        $config = ORMSetup::createAttributeMetadataConfiguration($entityPaths, $isDevMode);
        $config->enableNativeLazyObjects(true);

        $dbNameKey = $env === 'test' ? 'DB_NAME_TEST' : 'DB_NAME';

        $connectionParams = [
            'host'     => getenv('DB_HOST') ?: '127.0.0.1',
            'port'     => (int) (getenv('DB_PORT') ?: 3306),
            'dbname'   => getenv($dbNameKey) ?: throw new RuntimeException("$dbNameKey is not set"),
            'user'     => getenv('DB_USER') ?: throw new RuntimeException('DB_USER is not set'),
            'password' => getenv('DB_PASSWORD') ?: throw new RuntimeException('DB_PASSWORD is not set'),
            'driver'   => 'pdo_mysql',
        ];

        $connection = DriverManager::getConnection($connectionParams, $config);

        return new EntityManager($connection, $config);
    }
}
