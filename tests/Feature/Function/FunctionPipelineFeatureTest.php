<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Function;

use Exact\Function\Composition;
use Exact\Function\Functions;
use Exact\Function\Pipe;
use PHPUnit\Framework\TestCase;

final class FunctionPipelineFeatureTest extends TestCase
{
    public function test_it_composes_and_pipes_transformations_in_order(): void
    {
        $addTax = Functions::partial(
            static fn (int $taxRate, int $amount): int => $amount + $taxRate,
            2,
        );
        $pipeline = Composition::pipe(
            static fn (int $value): int => $value * 10,
            $addTax,
        );

        $result = Pipe::of(4)->through($pipeline)->get();

        self::assertSame(42, $result);
        self::assertSame(42, Composition::compose(
            static fn (int $value): int => $value + 2,
            static fn (int $value): int => $value * 10,
        )(4));
    }

    public function test_it_applies_a_curried_function_as_a_pipeline_step(): void
    {
        $multiply = Functions::curry(
            static fn (int $first, int $second, int $third): int => $first * $second * $third,
            3,
        );

        self::assertSame(24, $multiply(2)(3)(4));
    }
}