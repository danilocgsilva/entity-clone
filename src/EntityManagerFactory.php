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
        ?EntityManagerConfigInterface $emConfig = null
    ): EntityManagerInterface
    {
        if (!Type::hasType(EncryptedStringType::NAME)) {
            Type::addType(EncryptedStringType::NAME, EncryptedStringType::class);
        }
        
        // If no config provided, try to load from environment
        if ($emConfig === null) {
            if (file_exists($projectRoot . '/.env')) {
                Dotenv::createImmutable($projectRoot)->load();
            }
            
            $emConfig = new DefaultEntityManagerConfig(
                host: getenv('DB_HOST') ?: '127.0.0.1',
                port: (int) (getenv('DB_PORT') ?: 3306),
                databaseName: self::getDatabaseNameFromEnv(),
                username: getenv('DB_USER') ?: '',
                password: getenv('DB_PASSWORD') ?: '',
                environment: getenv('APP_ENV') ?: 'dev'
            );
        }

        $env = $emConfig->getEnvironment();
        $isDevMode = $env !== 'prod';

        $ormConfig = ORMSetup::createAttributeMetadataConfiguration($entityPaths, $isDevMode);
        $ormConfig->enableNativeLazyObjects(true);

        $connectionParams = [
            'host'     => $emConfig->getHost(),
            'port'     => $emConfig->getPort(),
            'dbname'   => $emConfig->getDatabaseName() ?: throw new RuntimeException("Database name is not set"),
            'user'     => $emConfig->getUsername() ?: throw new RuntimeException('Database username is not set'),
            'password' => $emConfig->getPassword() ?: throw new RuntimeException('Database password is not set'),
            'driver'   => 'pdo_mysql',
        ];

        $connection = DriverManager::getConnection($connectionParams, $ormConfig);

        return new EntityManager($connection, $ormConfig);
    }

    private static function getDatabaseNameFromEnv(): string
    {
        $env = getenv('APP_ENV') ?: 'dev';
        $dbNameKey = $env === 'test' ? 'DB_NAME_TEST' : 'DB_NAME';
        return getenv($dbNameKey) ?: '';
    }
}
