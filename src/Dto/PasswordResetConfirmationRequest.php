<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PasswordResetConfirmationRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 64, max: 64)]
        public string $token,
        #[Assert\NotBlank]
        #[Assert\Length(min: 12, max: 72)]
        #[Assert\Regex(
            pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            message: 'Password must contain at least one uppercase letter, one lowercase letter and one number.',
        )]
        public string $newPassword,
    ) {
    }
}
