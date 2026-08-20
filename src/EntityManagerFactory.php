<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone;

use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\DriverManager;
use Dotenv\Dotenv;
use RuntimeException;
use Danilocgsilva\EntityClone\Types\EncryptedStringType;
use Doctrine\DBAL\Types\Type;

final class EntityManagerFactory
{
    public static function create(
        string $projectRoot, 
        array $entityPaths,
        ?EntityManagerConfigInterface $config = null
    ): EntityManagerInterface
    {
        // If no config provided, try to load from environment
        if ($config === null) {
            if (file_exists($projectRoot . '/.env')) {
                Dotenv::createImmutable($projectRoot)->load();
            }
            
            $config = new DefaultEntityManagerConfig(
                host: getenv('DB_HOST') ?: '127.0.0.1',
                port: (int) (getenv('DB_PORT') ?: 3306),
                databaseName: self::getDatabaseNameFromEnv(),
                username: getenv('DB_USER') ?: '',
                password: getenv('DB_PASSWORD') ?: '',
                environment: getenv('APP_ENV') ?: 'dev'
            );
        }

        $env = $config->getEnvironment();
        $isDevMode = $env !== 'prod';

        $config = ORMSetup::createAttributeMetadataConfiguration($entityPaths, $isDevMode);
        $config->enableNativeLazyObjects(true);

        $connectionParams = [
            'host'     => $config->getHost(),
            'port'     => $config->getPort(),
            'dbname'   => $config->getDatabaseName() ?: throw new RuntimeException("Database name is not set"),
            'user'     => $config->getUsername() ?: throw new RuntimeException('Database username is not set'),
            'password' => $config->getPassword() ?: throw new RuntimeException('Database password is not set'),
            'driver'   => 'pdo_mysql',
        ];

        $connection = DriverManager::getConnection($connectionParams, $config);

        return new EntityManager($connection, $config);
    }

    private static function getDatabaseNameFromEnv(): string
    {
        $env = getenv('APP_ENV') ?: 'dev';
        $dbNameKey = $env === 'test' ? 'DB_NAME_TEST' : 'DB_NAME';
        return getenv($dbNameKey) ?: '';
    }
}
