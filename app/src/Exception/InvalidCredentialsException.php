<?php

declare(strict_types=1);

namespace App\Exception;

class InvalidCredentialsException extends InvalidRequestException
{
    public function __construct(?string $message = 'Invalid Credentials')
    {
        parent::__construct($message);
    }
}
