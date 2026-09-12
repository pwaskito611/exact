<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Validated;

use Exact\Data\Validated\Invalid;
use PHPUnit\Framework\TestCase;

final class InvalidTest extends TestCase
{
    public function testInvalidIsAlwaysInvalid(): void
    {
        $invalid = new Invalid('error');

        self::assertFalse($invalid->isValid());
        self::assertTrue($invalid->isInvalid());
    }

    public function testInvalidStoresError(): void
    {
        $invalid = new Invalid('error');

        self::assertSame('error', $invalid->get());
        self::assertSame('error', $invalid->getInvalid());
    }

    public function testInvalidCanContainNull(): void
    {
        $invalid = new Invalid(null);

        self::assertTrue($invalid->isInvalid());
        self::assertNull($invalid->getInvalid());
    }
}