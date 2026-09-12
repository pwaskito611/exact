<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Validated;

use Exact\Data\Validated\Invalid;
use Exact\Data\Validated\Validated;
use PHPUnit\Framework\TestCase;

final class InvalidTest extends TestCase
{
    public function testInvalidIsAlwaysInvalid(): void
    {
        $invalid = Validated::invalid('error');

        self::assertInstanceOf(Invalid::class, $invalid);
        self::assertFalse($invalid->isValid());
        self::assertTrue($invalid->isInvalid());
    }

    public function testInvalidStoresError(): void
    {
        $invalid = Validated::invalid('error');

        self::assertSame('error', $invalid->get());
        self::assertSame('error', $invalid->getInvalid());
    }

    public function testInvalidCanContainNull(): void
    {
        $invalid = Validated::invalid(null);

        self::assertTrue($invalid->isInvalid());
        self::assertNull($invalid->getInvalid());
    }
}