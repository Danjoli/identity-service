<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ApiDocumentationController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ApiDocumentationControllerTest extends TestCase
{
    public function testDocumentationCanBeDisabled(): void
    {
        $controller = new ApiDocumentationController(false, __DIR__);

        $this->expectException(NotFoundHttpException::class);
        $controller->index();
    }
}
