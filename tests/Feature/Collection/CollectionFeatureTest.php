<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Collection;

use Exact\Collection\LazySeq;
use Exact\Collection\Map;
use Exact\Collection\NonEmptySeq;
use Exact\Collection\Seq;
use Exact\Collection\Set;
use PHPUnit\Framework\TestCase;

final class CollectionFeatureTest extends TestCase
{
    public function test_it_processes_eager_collections_without_mutating_inputs(): void
    {
        $numbers = Seq::of(1, 2, 3, 4);
        $evenSquares = $numbers
            ->filter(static fn (int $number): bool => $number % 2 === 0)
            ->map(static fn (int $number): int => $number ** 2);
        $tags = Set::of('php', 'php', 'functional')->add('domain');
        $prices = Map::of('book', 10, 'pen', 3)->map(
            static fn (int $price): int => $price * 2,
        );

        self::assertSame([1, 2, 3, 4], $numbers->toArray());
        self::assertSame([4, 16], $evenSquares->toArray());
        self::assertSame(['php', 'functional', 'domain'], $tags->toArray());
        self::assertSame(['book' => 20, 'pen' => 6], $prices->toArray());
    }

    public function test_it_keeps_a_lazy_pipeline_bounded_and_non_empty_values_typed(): void
    {
        $evaluated = 0;
        $values = LazySeq::defer(function () use (&$evaluated): iterable {
            for ($number = 1; $number <= 100; $number++) {
                $evaluated++;
                yield $number;
            }
        });

        $result = $values
            ->filter(static fn (int $number): bool => $number % 2 === 0)
            ->map(static fn (int $number): int => $number * 10)
            ->take(3)
            ->toList();
        $nonEmpty = NonEmptySeq::of('first', 'second')->append('third');

        self::assertSame([20, 40, 60], $result->toArray());
        self::assertSame(6, $evaluated);
        self::assertSame(['first', 'second', 'third'], $nonEmpty->toArray());
    }
}