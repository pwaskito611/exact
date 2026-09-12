<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Function;

use Exact\Function\Composition;
use PHPUnit\Framework\TestCase;

final class CompositionTest extends TestCase
{
    public function testComposeAppliesFunctionsRightToLeft(): void
    {
        $function = Composition::compose(
            fn (int $value): int => $value + 10,
            fn (int $value): int => $value * 2,
        );

        // 5 * 2 = 10
        // 10 + 10 = 20
        self::assertSame(20, $function(5));
    }

    public function testPipeAppliesFunctionsLeftToRight(): void
    {
        $function = Composition::pipe(
            fn (int $value): int => $value + 10,
            fn (int $value): int => $value * 2,
        );

        // 5 + 10 = 15
        // 15 * 2 = 30
        self::assertSame(30, $function(5));
    }

    public function testComposeSupportsMultipleFunctions(): void
    {
        $function = Composition::compose(
            fn (int $value): int => $value + 1,
            fn (int $value): int => $value * 2,
            fn (int $value): int => $value - 3,
        );

        // 5 - 3 = 2
        // 2 * 2 = 4
        // 4 + 1 = 5
        self::assertSame(5, $function(5));
    }

    public function testPipeSupportsMultipleFunctions(): void
    {
        $function = Composition::pipe(
            fn (int $value): int => $value + 1,
            fn (int $value): int => $value * 2,
            fn (int $value): int => $value - 3,
        );

        // 5 + 1 = 6
        // 6 * 2 = 12
        // 12 - 3 = 9
        self::assertSame(9, $function(5));
    }

    public function testComposeWithNoFunctionsReturnsIdentity(): void
    {
        $function = Composition::compose();

        self::assertSame(42, $function(42));
    }

    public function testPipeWithNoFunctionsReturnsIdentity(): void
    {
        $function = Composition::pipe();

        self::assertSame(42, $function(42));
    }
}