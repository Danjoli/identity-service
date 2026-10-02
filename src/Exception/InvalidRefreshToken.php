<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidRefreshToken extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Refresh token is invalid, expired or revoked.');
    }
}
