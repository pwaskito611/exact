<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Data;

use Exact\Data\Result\Result;
use PHPUnit\Framework\TestCase;

final class ResultFeatureTest extends TestCase
{
    public function test_it_composes_a_successful_operation_and_renders_it(): void
    {
        $message = Result::ok(4)
            ->flatMap(static fn (int $value): Result => Result::ok($value * 3))
            ->map(static fn (int $value): string => "total: {$value}")
            ->fold(
                static fn (mixed $error): string => "error: {$error}",
                static fn (string $value): string => $value,
            );

        self::assertSame('total: 12', $message);
    }

    public function test_it_preserves_and_transforms_an_error_path(): void
    {
        $message = Result::err('out of stock')
            ->map(static fn (int $value): int => $value + 1)
            ->mapErr(static fn (string $error): string => strtoupper($error))
            ->getOrElse('fallback');

        self::assertSame('fallback', $message);
        self::assertSame('OUT OF STOCK', Result::err('out of stock')->mapErr(
            static fn (string $error): string => strtoupper($error),
        )->error());
    }
}