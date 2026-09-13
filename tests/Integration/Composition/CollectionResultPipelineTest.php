<?php

declare(strict_types=1);

namespace Exact\Tests\Integration\Composition;

use Exact\Collection\LazySeq;
use Exact\Collection\Seq;
use Exact\Data\Result\Result;
use PHPUnit\Framework\TestCase;

final class CollectionResultPipelineTest extends TestCase
{
    public function test_it_converts_a_lazy_input_pipeline_into_a_result(): void
    {
        $records = LazySeq::defer(static function (): iterable {
            yield ['name' => 'book', 'price' => 10];
            yield ['name' => 'pen', 'price' => 3];
            yield ['name' => 'bag', 'price' => 25];
        });

        $total = $records
            ->filter(static fn (array $record): bool => $record['price'] >= 5)
            ->map(static fn (array $record): Result => Result::ok($record['price']))
            ->toList()
            ->foldLeft(
                Result::ok(0),
                static fn (Result $result, Result $price): Result => $result->flatMap(
                    static fn (int $current): Result => $price->map(
                        static fn (int $value): int => $current + $value,
                    ),
                ),
            );

        self::assertSame(35, $total->get());
        self::assertSame([10, 25], $records->filter(
            static fn (array $record): bool => $record['price'] >= 5,
        )->map(static fn (array $record): int => $record['price'])->toList()->toArray());
    }
}