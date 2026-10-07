<?php

declare(strict_types=1);

namespace Exact\Tests\Integration\Pattern;

use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;
use Exact\Type\Either\Either;
use PHPUnit\Framework\TestCase;

final class ResultPatternIntegrationTest extends TestCase
{
    public function test_it_turns_a_payment_variant_into_an_either(): void
    {
        $payment = Variant::of('Failed', ['code' => 'DECLINED']);

        $result = PatternMatch::on($payment)
            ->case(
                'Paid',
                static fn (Variant $variant): Either => Either::right($variant->value()['id']),
            )
            ->case(
                'Failed',
                static fn (Variant $variant): Either => Either::left($variant->value()['code']),
            )
            ->default(static fn (Variant $variant): Either => Either::left("unknown payment state: {$variant->name()}"))
            ->run();

        self::assertTrue($result->isLeft());
        self::assertSame('DECLINED', $result->getLeft());
    }
}