<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RefreshTokenRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 64, max: 128)]
        public string $refreshToken,
    ) {
    }
}
