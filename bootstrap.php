<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DriverManager;

$paths = [__DIR__ . './src/Entity'];
$env = getenv('APP_ENV') ?: 'dev';
$isDevMode = $env !== 'prod';

$config = ORMSetup::craeteAttributeMetadataConfiguration($paths, $isDevMode);

$dbNameEnvKey = $env === 'test' ? 'DB_NAME_TEST' : 'DB_NAME';

$connectionsParams = [
    'host' => getenv('DB_HOST') ?: throw new RuntimeException('DB_HOST is not set.'),
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'dbname' => getenv($dbNameEnvKey) ?: throw new RuntimeException("{$dbNameEnvKey} is not set."),
    'user' => getenv('DB_USER') ?: throw new RuntimeException("DB_USER is not set."),
    'password' => getenv('DB_PASSWORD') ?: throw new RuntimeException("DB_PASSWORD is not set."),
    'driver' => 'pdo_mysql'
];

$connection = DriverManager::getConnection($connectionsParams, $config);
$entityManager = new EntityManager($connection, $config);

return $entityManager;
