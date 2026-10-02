<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\UpdateProfileRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UpdateProfile
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function update(User $user, UpdateProfileRequest $request): User
    {
        $user->changeDisplayName($request->displayName);
        $this->entityManager->flush();

        return $user;
    }
}
