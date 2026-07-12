<?php

namespace App\Exceptions;

use Exception;

class InsufficientPointsException extends Exception
{
    public function __construct()
    {
        parent::__construct('Insufficient points balance.');
    }
}
