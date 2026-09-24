<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone\Exception;

use Exception;

class MissingTargetDatabase extends Exception
{
    public function __construct(string $databaseName, ?Exception $previous = null)
    {
        $message = "Target database '{$databaseName}' does not exist in target connection.";
        parent::__construct($message, 0, $previous);
    }
}