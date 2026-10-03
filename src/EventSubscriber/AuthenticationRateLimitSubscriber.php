<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class AuthenticationRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private string $environment,
        private RateLimiterFactory $loginLimiter,
        private RateLimiterFactory $registrationLimiter,
        private RateLimiterFactory $recoveryLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 20]];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || 'POST' !== $request->getMethod()) {
            return;
        }

        if ('test' === $this->environment && '1' !== $request->headers->get('X-Test-Rate-Limit')) {
            return;
        }

        $path = $request->getPathInfo();
        $limiter = match ($path) {
            '/api/v1/auth/login' => $this->loginLimiter,
            '/api/v1/auth/register' => $this->registrationLimiter,
            '/api/v1/auth/email/resend', '/api/v1/auth/password/request' => $this->recoveryLimiter,
            default => null,
        };
        if (null === $limiter) {
            return;
        }

        $key = $request->getClientIp() ?? 'unknown';
        if ('/api/v1/auth/login' === $path) {
            $key .= '|'.$this->loginIdentity($request);
        }

        $limit = $limiter->create($key)->consume();
        if (!$limit->isAccepted()) {
            $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());
            throw new TooManyRequestsHttpException($retryAfter, 'Too many authentication attempts.');
        }
    }

    private function loginIdentity(Request $request): string
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !isset($payload['email']) || !is_string($payload['email'])) {
            return 'unknown';
        }

        return mb_strtolower(trim($payload['email']));
    }
}
