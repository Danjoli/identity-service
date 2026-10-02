<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\AccountStatus;
use App\Enum\UserRole;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserAuthorizationRequest
{
    /** @param list<string> $roles */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\Choice(callback: [UserRole::class, 'values'])])]
        public array $roles,
        #[Assert\Choice(callback: [AccountStatus::class, 'values'])]
        public string $status,
    ) {
    }
}
