<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 120)]
        public string $displayName,

        #[Assert\NotBlank]
        #[Assert\Length(min: 12, max: 72)]
        #[Assert\Regex(
            pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            message: 'Password must contain at least one uppercase letter, one lowercase letter and one number.',
        )]
        public string $password,
    ) {
    }
}
