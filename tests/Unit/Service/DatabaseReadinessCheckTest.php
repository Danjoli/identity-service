<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Controller\HealthController;
use App\Service\DatabaseReadinessCheck;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class DatabaseReadinessCheckTest extends TestCase
{
    public function testDatabaseFailureMarksServiceAsNotReady(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('executeQuery')
            ->with('SELECT 1')
            ->willThrowException(new \RuntimeException('database unavailable'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with('Database readiness check failed.', ['exception_class' => \RuntimeException::class]);

        $response = (new HealthController())->ready(new DatabaseReadinessCheck($connection, $logger));

        self::assertSame(503, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"status":"not_ready","checks":{"database":"down"}}',
            (string) $response->getContent(),
        );
    }
}
