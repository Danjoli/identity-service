<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidEmailVerificationToken extends \DomainException
{
    public function __construct()
    {
        parent::__construct('The e-mail verification token is invalid or expired.');
    }
}
