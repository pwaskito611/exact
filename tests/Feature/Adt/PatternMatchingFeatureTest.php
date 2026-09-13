<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Adt;

use Exact\Adt\PatternMatch;
use Exact\Adt\Variant;
use PHPUnit\Framework\TestCase;

final class PatternMatchingFeatureTest extends TestCase
{
    public function test_it_dispatches_a_domain_variant_to_its_case(): void
    {
        $state = Variant::of('Paid', ['receipt' => 'R-100']);

        $message = PatternMatch::on($state)
            ->case('Pending', static fn (): string => 'waiting')
            ->case(
                'Paid',
                static fn (array $payment): string => "receipt: {$payment['receipt']}",
            )
            ->default(static fn (mixed $value, string $name): string => "unknown: {$name}")
            ->run();

        self::assertSame('receipt: R-100', $message);
    }

    public function test_it_uses_the_default_for_an_unhandled_variant(): void
    {
        $message = PatternMatch::on(Variant::of('Cancelled', 'customer request'))
            ->case('Paid', static fn (): string => 'paid')
            ->default(
                static fn (string $reason, string $name): string => "{$name}: {$reason}",
            )
            ->run();

        self::assertSame('Cancelled: customer request', $message);
    }
}