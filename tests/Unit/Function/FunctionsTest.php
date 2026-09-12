<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Function;

use Exact\Function\Functions;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FunctionsTest extends TestCase
{
    public function testIdentityReturnsSameValue(): void
    {
        $identity = Functions::identity();

        self::assertSame('hello', $identity('hello'));
        self::assertSame(42, $identity(42));
    }

    public function testConstantAlwaysReturnsSameValue(): void
    {
        $constant = Functions::constant('hello');

        self::assertSame('hello', $constant());
        self::assertSame('hello', $constant(1));
        self::assertSame('hello', $constant(1, 2, 3));
    }

    public function testCurryAppliesArgumentsOneByOne(): void
    {
        $add = Functions::curry(
            fn (int $a, int $b, int $c): int => $a + $b + $c,
            3,
        );

        self::assertSame(
            60,
            $add(10)(20)(30),
        );
    }

    public function testCurrySupportsPartialApplication(): void
    {
        $multiply = Functions::curry(
            fn (int $a, int $b, int $c): int => $a * $b * $c,
            3,
        );

        $byTwo = $multiply(2);
        $byTwoAndThree = $byTwo(3);

        self::assertSame(
            24,
            $byTwoAndThree(4),
        );
    }

    public function testCurryRejectsInvalidArity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Functions::curry(
            fn (int $a): int => $a,
            0,
        );
    }

    public function testPartialApplication(): void
    {
        $addTen = Functions::partial(
            fn (int $a, int $b): int => $a + $b,
            10,
        );

        self::assertSame(
            15,
            $addTen(5),
        );
    }

    public function testPartialApplicationSupportsMultipleArguments(): void
    {
        $calculate = Functions::partial(
            fn (int $a, int $b, int $c): int => $a + $b + $c,
            10,
            20,
        );

        self::assertSame(
            60,
            $calculate(30),
        );
    }
}