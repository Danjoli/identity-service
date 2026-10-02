<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidCredentials extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}
