<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone\Exceptions;

use Exception;

class TargetTableAlreadyExists extends Exception
{
    public function __construct(string $tableName, ?Exception $previous = null)
    {
        $message = "Target table '{$tableName}' already exists at target.";
        parent::__construct($message, 0, $previous);
    }
}
