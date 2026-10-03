<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Observability\RequestContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

final readonly class HttpObservabilitySubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 110],
            KernelEvents::RESPONSE => ['onResponse', -110],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $provided = $request->headers->get('X-Request-ID');
        $request->attributes->set(RequestContext::REQUEST_ID, is_string($provided) && Uuid::isValid($provided)
            ? $provided
            : Uuid::v7()->toRfc4122());
        $request->attributes->set(RequestContext::START_TIME, microtime(true));
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = $request->attributes->getString(RequestContext::REQUEST_ID);
        $startedAt = $request->attributes->get(RequestContext::START_TIME);
        $duration = is_float($startedAt) ? (microtime(true) - $startedAt) * 1000 : 0.0;

        $event->getResponse()->headers->set('X-Request-ID', $requestId);
        $this->logger->info('HTTP request completed.', [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'status_code' => $event->getResponse()->getStatusCode(),
            'duration_ms' => round($duration, 2),
        ]);
    }
}
