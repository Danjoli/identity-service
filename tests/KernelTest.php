<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class KernelTest extends KernelTestCase
{
    public function testKernelBootsInTestEnvironment(): void
    {
        $kernel = self::bootKernel();

        self::assertSame('test', $kernel->getEnvironment());
        self::assertNotNull(self::getContainer());
    }
}
