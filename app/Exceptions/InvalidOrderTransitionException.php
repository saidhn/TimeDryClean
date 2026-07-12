<?php

namespace App\Exceptions;

use Exception;

class InvalidOrderTransitionException extends Exception
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("Cannot transition order from '{$from}' to '{$to}'.");
    }
}
