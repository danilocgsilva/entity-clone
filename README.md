# Entity Clone

This is a PHP package holding code assets to deal with database structure and the rules of package.

## What will you see here

The Doctrine bundle packages for database management, including migration, entities and repositories.

You may inherit the data from Doctrine packages to control migrations through parent project.

## Database dependency

Don't you forget that this project is heavily dependenty upon a real database.

Take a look to the `env.example` to be aware of what variables it is required to work.

## Start by a command

Although this package is designed to work *as a package*, it provides by its own a basic database management script. You can install the package with composer and take advantage of `bin/doctrine-migrations.php` script to make the first database manipulation. This file also will teach you the basics at how to call the scripts through the parent project, that is the ideal way to use this package.

## Database Configuration

The package supports two ways of configuration:

### 1. Environment Variables (Default)
Set the following environment variables in your project:
- `DB_HOST` (default: 127.0.0.1)
- `DB_NAME` (default: empty)
- `DB_USER` (default: empty)
- `DB_PASSWORD` (default: empty)
- `DB_PORT` (default: 3306)
- `DB_NAME_TEST` (for test environment)
- `APP_ENV` (default: dev, set to 'test' for testing)

### 2. Programmatic Configuration
In your application that requires this package, you can pass a custom configuration:

```php
use Danilocgsilva\EntityClone\EntityManagerFactory;
use Danilocgsilva\EntityClone\DefaultEntityManagerConfig;

$config = new DefaultEntityManagerConfig(
    host: 'your-db-host',
    port: 3306,
    databaseName: 'your_database_name',
    username: 'your_username',
    password: 'your_password',
    environment: 'prod'
);

$entityManager = EntityManagerFactory::create(
    projectRoot: __DIR__,
    entityPaths: [__DIR__ . '/src/Entities'],
    config: $config
);
```