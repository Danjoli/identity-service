<?php

declare(strict_types=1);

namespace App\Observability;

final class RequestContext
{
    public const REQUEST_ID = '_request_id';
    public const START_TIME = '_request_start_time';
}
