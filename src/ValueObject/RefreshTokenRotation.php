<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Entity\User;

final readonly class RefreshTokenRotation
{
    public function __construct(
        public User $user,
        public string $refreshToken,
    ) {
    }
}
