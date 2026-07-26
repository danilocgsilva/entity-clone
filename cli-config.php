<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Danilocgsilva\EntityClone\EntityManagerFactory;

return EntityManagerFactory::create(
    projectRoot: __DIR__,
    entityPaths: [__DIR__ . '/src/Entity'],
);