<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuditEvent;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

final readonly class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
    ) {
    }

    /** @param array<string, mixed> $context */
    public function record(string $eventType, ?User $actor, ?User $subject, array $context = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $requestId = $request?->attributes->get('_api_request_id');
        $this->entityManager->persist(new AuditEvent(
            $eventType,
            $actor?->getId()->toRfc4122(),
            $actor?->getEmail(),
            $subject?->getId()->toRfc4122(),
            $subject?->getEmail(),
            is_string($requestId) ? $requestId : Uuid::v7()->toRfc4122(),
            $request?->getClientIp(),
            $this->redact($context),
        ));
        $this->entityManager->flush();
    }

    /** @param array<mixed, mixed> $context
     * @return array<string, mixed>
     */
    private function redact(array $context): array
    {
        $redacted = [];
        foreach ($context as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (preg_match('/password|token|secret|credential/i', $key)) {
                $redacted[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redact($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }
}
