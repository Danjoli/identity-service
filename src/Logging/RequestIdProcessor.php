<?php

declare(strict_types=1);

namespace App\Logging;

use App\Observability\RequestContext;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class RequestIdProcessor implements ProcessorInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $requestId = $this->requestStack->getCurrentRequest()?->attributes->get(RequestContext::REQUEST_ID);
        if (!is_string($requestId) || '' === $requestId) {
            return $record;
        }

        return $record->with(extra: [...$record->extra, 'request_id' => $requestId]);
    }
}
