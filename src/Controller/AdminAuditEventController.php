<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AuditEvent;
use App\Repository\AuditEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AdminAuditEventController extends AbstractController
{
    #[Route('/api/v1/admin/audit-events', name: 'api_v1_admin_audit_events', methods: ['GET'], format: 'json')]
    public function __invoke(AuditEventRepository $events): JsonResponse
    {
        return $this->json(['data' => array_map(self::eventData(...), $events->recent())]);
    }

    /** @return array<string, mixed> */
    private static function eventData(AuditEvent $event): array
    {
        return [
            'id' => $event->getId()->toRfc4122(), 'eventType' => $event->getEventType(),
            'actor' => ['id' => $event->getActorId(), 'email' => $event->getActorEmail()],
            'subject' => ['id' => $event->getSubjectId(), 'email' => $event->getSubjectEmail()],
            'requestId' => $event->getRequestId(), 'ipAddress' => $event->getIpAddress(),
            'context' => $event->getContext(), 'createdAt' => $event->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
