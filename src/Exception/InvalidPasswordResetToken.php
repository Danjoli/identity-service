<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidPasswordResetToken extends \DomainException
{
    public function __construct()
    {
        parent::__construct('The password reset token is invalid or expired.');
    }
}
