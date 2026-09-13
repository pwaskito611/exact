<?php

declare(strict_types=1);

namespace Exact\Tests\Integration\Adt;

use Exact\Adt\PatternMatch;
use Exact\Adt\Variant;
use Exact\Data\Result\Result;
use PHPUnit\Framework\TestCase;

final class ResultPatternIntegrationTest extends TestCase
{
    public function test_it_turns_a_payment_variant_into_a_result(): void
    {
        $payment = Variant::of('Failed', ['code' => 'DECLINED']);

        $result = PatternMatch::on($payment)
            ->case(
                'Paid',
                static fn (array $receipt): Result => Result::ok($receipt['id']),
            )
            ->case(
                'Failed',
                static fn (array $failure): Result => Result::err($failure['code']),
            )
            ->default(static fn (): Result => Result::err('unknown payment state'))
            ->run();

        self::assertTrue($result->isErr());
        self::assertSame('DECLINED', $result->error());
    }
}