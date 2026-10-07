<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Type;

use Exact\Type\Either\Either;
use PHPUnit\Framework\TestCase;

final class ResultFeatureTest extends TestCase
{
    public function test_it_composes_a_successful_operation_and_renders_it(): void
    {
        $message = Either::right(4)
            ->flatMap(static fn (int $value): Either => Either::right($value * 3))
            ->map(static fn (int $value): string => "total: {$value}")
            ->fold(
                static fn (mixed $error): string => "error: {$error}",
                static fn (string $value): string => $value,
            );

        self::assertSame('total: 12', $message);
    }

    public function test_it_preserves_and_transforms_an_error_path(): void
    {
        $message = Either::left('out of stock')
            ->mapLeft(static fn (string $error): string => strtoupper($error))
            ->orElseGet(static fn (): Either => Either::right('fallback'))
            ->getOrElse('fallback');

        self::assertSame('fallback', $message);
        self::assertSame('OUT OF STOCK', Either::left('out of stock')->mapLeft(
            static fn (string $error): string => strtoupper($error),
        )->getLeft());
    }
}