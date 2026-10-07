<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Pattern;

use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;
use PHPUnit\Framework\TestCase;

final class PatternMatchingFeatureTest extends TestCase
{
    public function test_it_dispatches_a_domain_variant_to_its_case(): void
    {
        $state = Variant::of('Paid', ['receipt' => 'R-100']);

        $message = PatternMatch::on($state)
            ->case('Pending', static fn (Variant $variant): string => "waiting: {$variant->name()}")
            ->case(
                'Paid',
                static fn (Variant $variant): string => "receipt: {$variant->value()['receipt']}",
            )
            ->default(static fn (Variant $variant): string => "unknown: {$variant->name()}")
            ->run();

        self::assertSame('receipt: R-100', $message);
    }

    public function test_it_uses_the_default_for_an_unhandled_variant(): void
    {
        $message = PatternMatch::on(Variant::of('Cancelled', 'customer request'))
            ->case('Paid', static fn (Variant $variant): string => "paid: {$variant->value()}")
            ->default(
                static fn (Variant $variant): string => "{$variant->name()}: {$variant->value()}",
            )
            ->run();

        self::assertSame('Cancelled: customer request', $message);
    }
}