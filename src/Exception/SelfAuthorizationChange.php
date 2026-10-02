<?php

declare(strict_types=1);

namespace App\Exception;

final class SelfAuthorizationChange extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Administrators cannot change their own roles or account status.');
    }
}
