<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Type;

use Exact\Type\Either\Either;
use PHPUnit\Framework\TestCase;

final class EitherFeatureTest extends TestCase
{
    public function test_it_composes_successful_domain_values_and_keeps_exceptions_out_of_the_flow(): void
    {
        $total = Either::right([
            'unit_price' => 120,
            'quantity' => 2,
        ])
            ->map(static fn (array $order): int => $order['unit_price'] * $order['quantity'])
            ->map(static fn (int $value): string => "total: {$value}")
            ->fold(
                static fn (mixed $error): string => "error: {$error}",
                static fn (string $value): string => $value,
            );

        self::assertSame('total: 240', $total);
    }

    public function test_it_preserves_left_errors_and_recover_lazily_when_the_domain_allows_it(): void
    {
        $message = Either::left('payment declined')
            ->mapLeft(static fn (string $error): string => strtoupper($error))
            ->orElseGet(static fn (): Either => Either::right('retry payment'))
            ->getOrElse('fallback');

        self::assertSame('retry payment', $message);
    }
}
