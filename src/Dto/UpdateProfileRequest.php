<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProfileRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 120)]
        public string $displayName,
    ) {
    }
}
