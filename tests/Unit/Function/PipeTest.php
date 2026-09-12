<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Function;

use Exact\Function\Pipe;
use PHPUnit\Framework\TestCase;

final class PipeTest extends TestCase
{
    public function testOfCreatesPipe(): void
    {
        self::assertSame(
            42,
            Pipe::of(42)->get(),
        );
    }

    public function testMapTransformsValue(): void
    {
        $pipe = Pipe::of(10);

        $result = $pipe->map(
            fn (int $value): int => $value * 2,
        );

        self::assertSame(20, $result->get());

        // Original remains unchanged.
        self::assertSame(10, $pipe->get());
    }

    public function testThroughAppliesFunctionsInOrder(): void
    {
        $result = Pipe::of(5)
            ->through(
                fn (int $value): int => $value + 10,
                fn (int $value): int => $value * 2,
            )
            ->get();

        self::assertSame(30, $result);
    }

    public function testThroughSupportsMultipleFunctions(): void
    {
        $result = Pipe::of('hello')
            ->through(
                fn (string $value): string => strtoupper($value),
                fn (string $value): string => $value . '!',
                fn (string $value): string => str_repeat($value, 2),
            )
            ->get();

        self::assertSame(
            'HELLO!HELLO!',
            $result,
        );
    }

    public function testThroughWithNoFunctionsKeepsValue(): void
    {
        self::assertSame(
            42,
            Pipe::of(42)->through()->get(),
        );
    }

    public function testPipeIsImmutable(): void
    {
        $pipe = Pipe::of(10);

        $result = $pipe->through(
            fn (int $value): int => $value * 2,
        );

        self::assertNotSame($pipe, $result);
        self::assertSame(10, $pipe->get());
        self::assertSame(20, $result->get());
    }
}