<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final readonly class DatabaseReadinessCheck
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
    ) {
    }

    public function isReady(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return true;
        } catch (\Throwable $exception) {
            $this->logger->warning('Database readiness check failed.', [
                'exception_class' => $exception::class,
            ]);

            return false;
        }
    }
}
