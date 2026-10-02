<?php

declare(strict_types=1);

namespace App\Exception;

final class EmailAlreadyExists extends \DomainException
{
    public function __construct()
    {
        parent::__construct('An account with this e-mail already exists.');
    }
}
