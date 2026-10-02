<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\LoginRequest;
use App\Entity\User;
use App\Exception\AccountDisabled;
use App\Exception\InvalidCredentials;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class AuthenticateUser
{
    public function __construct(
        private UserRepository $users,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function authenticate(LoginRequest $request): User
    {
        $user = $this->users->findOneByEmail($request->email);

        if (null === $user || !$this->passwordHasher->isPasswordValid($user, $request->password)) {
            throw new InvalidCredentials();
        }

        if (!$user->isActive()) {
            throw new AccountDisabled();
        }

        return $user;
    }
}
