#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use MyVendor\DoctrineDbToolkit\EntityManagerFactory;
use Symfony\Component\Console\Application;

$entityManager = EntityManagerFactory::create(
    projectRoot: __DIR__ . '/..',
    entityPaths: [__DIR__ . '/../src/Entity'],
);

$config = new PhpFile(__DIR__ . '/../config/migrations.php');
$dependencyFactory = DependencyFactory::fromEntityManager(
    $config,
    new ExistingConnection($entityManager->getConnection()),
);

$app = new Application('Doctrine Migrations');
$app->addCommands([
    new DiffCommand($dependencyFactory),
    new MigrateCommand($dependencyFactory),
    new StatusCommand($dependencyFactory),
]);
$app->run();
