# Criando uma aplicação mínima para migrations com composer

## Question 1:

Show me the minimum required code to deal with database (MySQL implementation), migrations and entities, using PHP and Doctrine.

## Answer 1:

Here's a minimal working setup for Doctrine ORM + MySQL with entities and migrations.

## 1. Install dependencies

```bash
composer require doctrine/orm doctrine/dbal doctrine/migrations symfony/cache
```

## 2. `bootstrap.php` — EntityManager setup

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DriverManager;

$paths = [__DIR__ . '/src/Entity'];
$isDevMode = true;

$config = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode);

$connectionParams = [
    'dbname'   => 'my_database',
    'user'     => 'root',
    'password' => 'secret',
    'host'     => '127.0.0.1',
    'driver'   => 'pdo_mysql',
];

$connection = DriverManager::getConnection($connectionParams, $config);
$entityManager = new EntityManager($connection, $config);

return $entityManager;
```

## 3. Entity — `src/Entity/User.php`

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }
}
```

## 4. `cli-config.php` — required for migrations CLI

```php
<?php
require_once __DIR__ . '/bootstrap.php';

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

$entityManager = require __DIR__ . '/bootstrap.php';

return ConsoleRunner::createHelperSet($entityManager);
```

## 5. `migrations.php` — migrations config

```php
<?php
return [
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
    ],
    'migrations_paths' => [
        'App\Migrations' => __DIR__ . '/migrations',
    ],
];
```

## 6. Vendor binary wiring (`vendor/bin/doctrine-migrations`)

If you didn't get the migrations CLI auto-wired, create `bin/doctrine-migrations.php`:

```php
#!/usr/bin/env php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Symfony\Component\Console\Application;

$entityManager = require __DIR__ . '/../bootstrap.php';

$config = new PhpFile(__DIR__ . '/../migrations.php');
$dependencyFactory = DependencyFactory::fromEntityManager(
    $config,
    new ExistingConnection($entityManager->getConnection()),
);

$app = new Application('Doctrine Migrations');
$app->addCommands([
    new DiffCommand($dependencyFactory),
    new MigrateCommand($dependencyFactory),
]);
$app->run();
```

## 7. Generate and run a migration

```bash
php bin/doctrine-migrations.php diff      # generates migration from entity changes
php bin/doctrine-migrations.php migrate   # applies pending migrations
```

## 8. Using it — basic CRUD

```php
<?php
$entityManager = require __DIR__ . '/bootstrap.php';

use App\Entity\User;

$user = new User();
$user->setName('Alice');
$user->setEmail('alice@example.com');

$entityManager->persist($user);
$entityManager->flush();

$found = $entityManager->getRepository(User::class)->findOneBy(['email' => 'alice@example.com']);
echo $found->getName();
```

That's the full minimal loop: define an entity with attributes → run `diff` to auto-generate a migration → run `migrate` to apply it to MySQL → use `EntityManager` for persistence.

A couple of things worth knowing as you scale this up:
- `diff` compares your entity mappings against the current DB schema, so always review the generated migration SQL before running `migrate` in production.
- For a real project you'd typically use Symfony's `doctrine/doctrine-bundle` or a framework-agnostic setup like this one — this example is the bare-metal version without a framework.

## Question 2:

Not check carefully your suggestion, but I want that option that automatically check the database schema and compares to the current code schema and write the required changes in migrations. Is such included?

## Answer 2:

Yes — that's exactly what the `diff` command does, and it's already included in the setup above. Let me be precise about how it works, since it's worth understanding the mechanics rather than just trusting it blindly.

## How `diff` works

```bash
php bin/doctrine-migrations.php diff
```

This command:
1. Reads your **current entity mappings** (the `#[ORM\Entity]`, `#[ORM\Column]`, etc. attributes in `src/Entity/*.php`)
2. Introspects the **actual live database schema** (connects to MySQL, reads tables/columns/indexes as they currently exist)
3. Computes the difference between the two
4. Generates a new PHP migration file in `migrations/` containing the `up()` (and `down()`) SQL needed to bring the database in line with your entities

Example output file: `migrations/Version20260724120000.php`

```php
<?php
declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD phone VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP phone');
    }
}
```

## Important caveats

- **It does not run automatically on every request/script execution** — you must explicitly invoke `diff` as a CLI step (e.g., in your dev workflow or CI), then explicitly run `migrate` to apply it. Doctrine deliberately does not auto-sync schema at runtime because that would be dangerous in production.
- **Always review the generated SQL before applying it.** The diff algorithm can sometimes produce destructive or unexpected statements — especially around renamed columns (it may see a rename as "drop + add," losing data) or changed types with ambiguous casting.
- It requires the database to be reachable and already have *some* schema present, or it'll generate a full `CREATE TABLE` migration for new entities.
- For a genuinely empty database, you'd run `diff` once to generate the initial full-schema migration, then `migrate`.

## Typical workflow

```bash
# 1. Edit/add entity classes
# 2. Generate migration from the diff
php bin/doctrine-migrations.php diff

# 3. Open the generated file and review the SQL
# 4. Apply it
php bin/doctrine-migrations.php migrate

# 5. Check status anytime
php bin/doctrine-migrations.php status
```

So: yes, it's built-in and does exactly what you're asking for — automatic detection of code-vs-database drift and generation of the migration to reconcile them — but the "review before apply" step is not optional in any serious project.

## Question 3

Show me the code to connect to the database through system variables.`

## Answer 3

Here's the setup using environment variables instead of hardcoded credentials.

## 1. Install a dotenv loader (optional but common)

```bash
composer require vlucas/phpdotenv
```

## 2. `.env` file (project root — never commit this)

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=my_database
DB_USER=root
DB_PASSWORD=secret
DB_DRIVER=pdo_mysql
```

Add `.env` to `.gitignore`. Optionally commit a `.env.example` with blank/placeholder values so others know what's needed.

## 3. `bootstrap.php` — reading from environment

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DriverManager;

// Load .env into $_ENV / getenv() if present (safe to skip in prod where
// real system/container env vars are already set)
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$paths = [__DIR__ . '/src/Entity'];
$isDevMode = (getenv('APP_ENV') ?: 'dev') === 'dev';

$config = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode);

$connectionParams = [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => (int) (getenv('DB_PORT') ?: 3306),
    'dbname'   => getenv('DB_NAME') ?: throw new RuntimeException('DB_NAME is not set'),
    'user'     => getenv('DB_USER') ?: throw new RuntimeException('DB_USER is not set'),
    'password' => getenv('DB_PASSWORD') ?: throw new RuntimeException('DB_PASSWORD is not set'),
    'driver'   => getenv('DB_DRIVER') ?: 'pdo_mysql',
];

$connection = DriverManager::getConnection($connectionParams, $config);
$entityManager = new EntityManager($connection, $config);

return $entityManager;
```

## 4. Setting real system/OS environment variables (no `.env` file, production style)

If you're deploying without a `.env` file — e.g. setting variables directly on the server, in a Docker container, or via your process manager — the code above still works unchanged, since `getenv()` reads from the actual system environment. Examples of setting them:

**Linux shell / systemd service / CI:**
```bash
export DB_HOST=127.0.0.1
export DB_NAME=my_database
export DB_USER=root
export DB_PASSWORD=secret
php your-script.php
```

**Docker Compose (`docker-compose.yml`):**
```yaml
services:
  app:
    build: .
    environment:
      DB_HOST: mysql
      DB_PORT: 3306
      DB_NAME: my_database
      DB_USER: root
      DB_PASSWORD: secret
    depends_on:
      - mysql
  mysql:
    image: mysql:8
    environment:
      MYSQL_DATABASE: my_database
      MYSQL_ROOT_PASSWORD: secret
```

**Apache/nginx + PHP-FPM (`www.conf` or vhost):**
```ini
env[DB_HOST] = 127.0.0.1
env[DB_NAME] = my_database
env[DB_USER] = dbuser
env[DB_PASSWORD] = secret
```
Note: with PHP-FPM you sometimes need `clear_env = no` in the pool config, or use `$_SERVER['DB_HOST']` instead of `getenv()`, depending on how FPM passes the variables through.

## 5. `cli-config.php` and `migrations` CLI

No change needed — since they both call `require __DIR__ . '/bootstrap.php'`, they automatically pick up the same environment-based connection.

## Key point

`getenv('DB_HOST')` reads **whatever is currently in the process environment**, regardless of whether that came from a `.env` file loaded by `phpdotenv`, an `export` in the shell, a Docker `environment:` block, or your CI/CD secrets manager — so this same code works identically across local dev and production, you just change *how* the variables get set, not the code that reads them.

## Question 4

I will use unit tests and also will make a real database connection when testing. Show me how to easily make the code switch between development and test database connection (notice: both development and test database will be under the same server, only with the database name changed). Did it change something to the code?

## Answer 4

Yes, it changes the bootstrap slightly — instead of one fixed `DB_NAME`, you pick the database name based on which environment is active. Since dev and test share the same server/credentials, only the `dbname` needs to switch.

## 1. Two `.env` files

**`.env`** (development)
```env
APP_ENV=dev
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=root
DB_PASSWORD=secret
DB_DRIVER=pdo_mysql
DB_NAME_DEV=my_database
DB_NAME_TEST=my_database_test
```

You don't need a second file necessarily — since host/user/password are identical, keeping everything in one `.env` with both DB names is simpler than duplicating files. The only thing that changes per-run is `APP_ENV`.

## 2. Updated `bootstrap.php`

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DriverManager;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$paths = [__DIR__ . '/src/Entity'];
$env = getenv('APP_ENV') ?: 'dev';
$isDevMode = $env !== 'prod';

$config = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode);

// Pick the database name based on environment; host/user/password stay shared
$dbNameKey = $env === 'test' ? 'DB_NAME_TEST' : 'DB_NAME_DEV';

$connectionParams = [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => (int) (getenv('DB_PORT') ?: 3306),
    'dbname'   => getenv($dbNameKey) ?: throw new RuntimeException("$dbNameKey is not set"),
    'user'     => getenv('DB_USER') ?: throw new RuntimeException('DB_USER is not set'),
    'password' => getenv('DB_PASSWORD') ?: throw new RuntimeException('DB_PASSWORD is not set'),
    'driver'   => getenv('DB_DRIVER') ?: 'pdo_mysql',
];

$connection = DriverManager::getConnection($connectionParams, $config);
$entityManager = new EntityManager($connection, $config);

return $entityManager;
```

The only real change from before: instead of a single `DB_NAME`, there are two candidate keys (`DB_NAME_DEV` / `DB_NAME_TEST`), and `APP_ENV` decides which one gets used. Everything else (host, user, password, driver) is untouched since the server is shared.

## 3. Setting `APP_ENV=test` for PHPUnit

Install PHPUnit if you haven't:
```bash
composer require --dev phpunit/phpunit
```

**`phpunit.xml`**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <php>
        <env name="APP_ENV" value="test" force="true"/>
    </php>
    <testsuites>
        <testsuite name="Integration">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

`force="true"` ensures PHPUnit's `APP_ENV=test` overrides whatever might already be set in the shell — important so a stray `export APP_ENV=dev` in your terminal doesn't leak into test runs.

## 4. Example test using the real database

```php
<?php
namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Entity\User;

class UserRepositoryTest extends TestCase
{
    private \Doctrine\ORM\EntityManager $em;

    protected function setUp(): void
    {
        // APP_ENV=test here, so this connects to DB_NAME_TEST
        $this->em = require __DIR__ . '/../bootstrap.php';
    }

    public function testCreateAndFetchUser(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');

        $this->em->persist($user);
        $this->em->flush();

        $found = $this->em->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $this->assertNotNull($found);
        $this->assertSame('Test User', $found->getName());
    }

    protected function tearDown(): void
    {
        // Clean up so tests stay independent/repeatable
        $conn = $this->em->getConnection();
        $conn->executeStatement('DELETE FROM users');
        $this->em->close();
    }
}
```

## 5. Running migrations against the test database

Since `cli-config.php` also goes through `bootstrap.php`, you switch environments the same way on the CLI:

```bash
# apply migrations to dev database
APP_ENV=dev php bin/doctrine-migrations.php migrate

# apply migrations to test database (run once before your test suite, or in CI setup)
APP_ENV=test php bin/doctrine-migrations.php migrate
```

## Summary of what changed

| Before | Now |
|---|---|
| One `DB_NAME` variable | Two: `DB_NAME_DEV`, `DB_NAME_TEST` |
| `dbname` read directly from `DB_NAME` | `dbname` resolved via `$env === 'test' ? 'DB_NAME_TEST' : 'DB_NAME_DEV'` |
| No `APP_ENV` dependency for connection | Connection now depends on `APP_ENV` to pick the DB name |
| — | `phpunit.xml` forces `APP_ENV=test` during test runs |

Host, port, user, password, and driver are all untouched — exactly as you specified, since it's the same server, just a different database name per environment. One practical note: make sure `my_database_test` actually exists on the server and has migrations applied before running tests, otherwise Doctrine will throw a connection/schema error on the first query.

## Question 5

Can you organize it in a composer package?

## Answer 5

Here's the same setup restructured as a proper installable Composer package — with its own namespace, PSR-4 autoloading, and a clean public API that a consuming application can require and configure.

## Package directory structure

```
my-vendor/doctrine-db-toolkit/
├── composer.json
├── .env.example
├── phpunit.xml
├── bin/
│   └── doctrine-migrations.php
├── src/
│   ├── EntityManagerFactory.php
│   ├── Entity/
│   │   └── User.php
│   └── Migrations/
│       └── .gitkeep
├── config/
│   └── migrations.php
├── cli-config.php
└── tests/
    └── UserRepositoryTest.php
```

## 1. `composer.json`

```json
{
    "name": "my-vendor/doctrine-db-toolkit",
    "description": "Minimal Doctrine ORM + MySQL setup with entities, migrations, and env-based dev/test switching",
    "type": "library",
    "require": {
        "php": ">=8.1",
        "doctrine/orm": "^2.19",
        "doctrine/dbal": "^3.8",
        "doctrine/migrations": "^3.7",
        "vlucas/phpdotenv": "^5.6",
        "symfony/cache": "^6.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5"
    },
    "autoload": {
        "psr-4": {
            "MyVendor\\DoctrineDbToolkit\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "MyVendor\\DoctrineDbToolkit\\Tests\\": "tests/"
        }
    },
    "bin": [
        "bin/doctrine-migrations.php"
    ]
}
```

Key change from the earlier scripts: everything now lives under the `MyVendor\DoctrineDbToolkit` namespace and is autoloaded via PSR-4, instead of loose files requiring each other by relative path.

## 2. `src/EntityManagerFactory.php` — the core logic, now a class

```php
<?php
declare(strict_types=1);

namespace MyVendor\DoctrineDbToolkit;

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

        $dbNameKey = $env === 'test' ? 'DB_NAME_TEST' : 'DB_NAME_DEV';

        $connectionParams = [
            'host'     => getenv('DB_HOST') ?: '127.0.0.1',
            'port'     => (int) (getenv('DB_PORT') ?: 3306),
            'dbname'   => getenv($dbNameKey) ?: throw new RuntimeException("$dbNameKey is not set"),
            'user'     => getenv('DB_USER') ?: throw new RuntimeException('DB_USER is not set'),
            'password' => getenv('DB_PASSWORD') ?: throw new RuntimeException('DB_PASSWORD is not set'),
            'driver'   => getenv('DB_DRIVER') ?: 'pdo_mysql',
        ];

        $connection = DriverManager::getConnection($connectionParams, $config);

        return new EntityManager($connection, $config);
    }
}
```

This replaces `bootstrap.php`. It's now a static factory method instead of a script that returns `$entityManager` — cleaner to consume from any application.

## 3. `src/Entity/User.php` — unchanged, just moved under the package namespace

```php
<?php
declare(strict_types=1);

namespace MyVendor\DoctrineDbToolkit\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }
}
```

## 4. `cli-config.php` — package root, used by the migrations CLI

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MyVendor\DoctrineDbToolkit\EntityManagerFactory;

return EntityManagerFactory::create(
    projectRoot: __DIR__,
    entityPaths: [__DIR__ . '/src/Entity'],
);
```

## 5. `config/migrations.php`

```php
<?php
declare(strict_types=1);

return [
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
    ],
    'migrations_paths' => [
        'MyVendor\\DoctrineDbToolkit\\Migrations' => __DIR__ . '/../src/Migrations',
    ],
];
```

## 6. `bin/doctrine-migrations.php`

```php
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
```

## 7. `.env.example` — committed as documentation, actual `.env` stays gitignored

```env
APP_ENV=dev
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=root
DB_PASSWORD=secret
DB_DRIVER=pdo_mysql
DB_NAME_DEV=my_database
DB_NAME_TEST=my_database_test
```

## 8. `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <php>
        <env name="APP_ENV" value="test" force="true"/>
    </php>
    <testsuites>
        <testsuite name="Integration">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

## 9. `tests/UserRepositoryTest.php`

```php
<?php
declare(strict_types=1);

namespace MyVendor\DoctrineDbToolkit\Tests;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use MyVendor\DoctrineDbToolkit\EntityManagerFactory;
use MyVendor\DoctrineDbToolkit\Entity\User;

final class UserRepositoryTest extends TestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = EntityManagerFactory::create(
            projectRoot: __DIR__ . '/..',
            entityPaths: [__DIR__ . '/../src/Entity'],
        );
    }

    public function testCreateAndFetchUser(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');

        $this->em->persist($user);
        $this->em->flush();

        $found = $this->em->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $this->assertNotNull($found);
        $this->assertSame('Test User', $found->getName());
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->executeStatement('DELETE FROM users');
        $this->em->close();
    }
}
```

## Usage once installed by a consuming project

```bash
composer require my-vendor/doctrine-db-toolkit
```

```php
<?php
require 'vendor/autoload.php';

use MyVendor\DoctrineDbToolkit\EntityManagerFactory;

$em = EntityManagerFactory::create(
    projectRoot: __DIR__,
    entityPaths: [__DIR__ . '/src/Entity'], // the consuming app's own entities
);
```

```bash
APP_ENV=dev  vendor/bin/doctrine-migrations.php migrate
APP_ENV=test vendor/bin/doctrine-migrations.php migrate
```

## What actually changed vs. the loose-script version

| Before | Now |
|---|---|
| `bootstrap.php` script returning `$entityManager` | `EntityManagerFactory::create()` static method, namespaced |
| Files wired by relative `require` paths | Wired by PSR-4 autoload + `composer.json` |
| `User` entity in global-ish `App\Entity` | `MyVendor\DoctrineDbToolkit\Entity\User`, ships inside the package |
| One-off project | Installable via `composer require`, versionable, reusable across projects |
| `migrations.php` referenced `App\Migrations` | Points to the package's own `MyVendor\DoctrineDbToolkit\Migrations` namespace |

One thing to flag: if a consuming application wants **its own** entities (not just the package's `User`), it should pass its own `entityPaths` array into `EntityManagerFactory::create()` rather than relying on the package's bundled entity — the `User` entity here is really just a usage example, not something you'd want every consumer stuck with.

===

Conversa com Claude Chat, 07/2026
