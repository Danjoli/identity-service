<?php

declare(strict_types=1);

namespace App\Enum;

enum AccountStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
