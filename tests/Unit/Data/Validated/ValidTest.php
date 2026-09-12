<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Validated;

use Exact\Data\Validated\Valid;
use PHPUnit\Framework\TestCase;

final class ValidTest extends TestCase
{
    public function testValidIsAlwaysValid(): void
    {
        $valid = new Valid('value');

        self::assertTrue($valid->isValid());
        self::assertFalse($valid->isInvalid());
    }

    public function testValidStoresValue(): void
    {
        $valid = new Valid('value');

        self::assertSame('value', $valid->get());
        self::assertSame('value', $valid->getValid());
    }

    public function testValidCanContainNull(): void
    {
        $valid = new Valid(null);

        self::assertTrue($valid->isValid());
        self::assertNull($valid->getValid());
    }
}