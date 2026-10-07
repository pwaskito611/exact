<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Type\Validated;

use Exact\Type\Validated\Valid;
use Exact\Type\Validated\Validated;
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

        self::assertSame('value', $valid->unsafeGet());
        self::assertSame('value', $valid->getValid());
    }

    public function testValidCanContainNull(): void
    {
        $valid = Validated::valid(null);

        self::assertTrue($valid->isValid());
        self::assertNull($valid->getValid());
    }
}