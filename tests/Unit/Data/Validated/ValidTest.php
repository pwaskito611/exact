<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Validated;

use Exact\Data\Validated\Valid;
use Exact\Data\Validated\Validated;
use PHPUnit\Framework\TestCase;

final class ValidTest extends TestCase
{
    public function testValidIsAlwaysValid(): void
    {
        $valid = Validated::valid('value');

        self::assertInstanceOf(Valid::class, $valid);
        self::assertTrue($valid->isValid());
        self::assertFalse($valid->isInvalid());
    }

    public function testValidStoresValue(): void
    {
        $valid = Validated::valid('value');

        self::assertSame('value', $valid->get());
        self::assertSame('value', $valid->getValid());
    }

    public function testValidCanContainNull(): void
    {
        $valid = Validated::valid(null);

        self::assertTrue($valid->isValid());
        self::assertNull($valid->getValid());
    }
}