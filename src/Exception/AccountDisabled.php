<?php

declare(strict_types=1);

namespace App\Exception;

final class AccountDisabled extends \DomainException
{
    public function __construct()
    {
        parent::__construct('This account is disabled.');
    }
}
