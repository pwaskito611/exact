<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Collection;

use Exact\Collection\LazySeq;
use Exact\Collection\Vec;
use Exact\Type\Option\Option;
use PHPUnit\Framework\TestCase;

final class LazySeqTest extends TestCase
{
    public function testFromCreatesLazySequence(): void
    {
        $evaluated = false;

        $lazy = LazySeq::from(
            (function () use (&$evaluated): \Generator {
                $evaluated = true;

                yield 1;
                yield 2;
                yield 3;
            })(),
        );

        self::assertFalse($evaluated);

        $result = $lazy->toSeq();

        self::assertTrue($evaluated);
        self::assertSame(
            [1, 2, 3],
            $result->toArray(),
        );
    }

    public function testDeferDelaysEvaluation(): void
    {
        $evaluated = false;

        $lazy = LazySeq::defer(
            function () use (&$evaluated): \Generator {
                $evaluated = true;

                yield 1;
                yield 2;
            },
        );

        self::assertFalse($evaluated);

        $lazy->toSeq();

        self::assertTrue($evaluated);
    }

    public function testMapIsLazy(): void
    {
        $mapped = 0;

        $lazy = LazySeq::from([1, 2, 3])
            ->map(function (int $value) use (&$mapped): int {
                $mapped++;

                return $value * 2;
            });

        self::assertSame(0, $mapped);

        $lazy->toSeq();

        self::assertSame(3, $mapped);
    }

    public function testFilterIsLazy(): void
    {
        $checked = 0;

        $lazy = LazySeq::from([1, 2, 3])
            ->filter(function (int $value) use (&$checked): bool {
                $checked++;

                return $value % 2 === 0;
            });

        self::assertSame(0, $checked);

        $lazy->toSeq();

        self::assertSame(3, $checked);
    }

    public function testTakeLimitsEvaluation(): void
    {
        $evaluated = 0;

        $lazy = LazySeq::defer(
            function () use (&$evaluated): \Generator {
                for ($i = 1; $i <= 100; $i++) {
                    $evaluated++;

                    yield $i;
                }
            },
        );

        $result = $lazy
            ->take(3)
            ->toSeq();

        self::assertSame(
            [1, 2, 3],
            $result->toArray(),
        );

        self::assertSame(3, $evaluated);
    }

    public function testTakeZeroProducesEmptySequence(): void
    {
        $evaluated = false;

        $lazy = LazySeq::defer(
            function () use (&$evaluated): \Generator {
                $evaluated = true;

                yield 1;
            },
        );

        $result = $lazy
            ->take(0)
            ->toSeq();

        self::assertSame([], $result->toArray());
        self::assertFalse($evaluated);
    }

    public function testMapAndFilterCanBeComposed(): void
    {
        $result = LazySeq::from([1, 2, 3, 4, 5])
            ->map(fn (int $value) => $value * 2)
            ->filter(fn (int $value) => $value > 5)
            ->toSeq();

        self::assertInstanceOf(Vec::class, $result);

        self::assertSame(
            [6, 8, 10],
            $result->toArray(),
        );
    }

    public function testLazySequenceCanProcessInfiniteSource(): void
    {
        $lazy = LazySeq::defer(
            function (): \Generator {
                $value = 1;

                while (true) {
                    yield $value++;
                }
            },
        );

        $result = $lazy
            ->map(fn (int $value) => $value * 2)
            ->filter(fn (int $value) => $value % 4 === 0)
            ->take(5)
            ->toSeq();

        self::assertSame(
            [4, 8, 12, 16, 20],
            $result->toArray(),
        );
    }

    public function testMemoizeCachesEvaluation(): void
    {
        $evaluated = 0;
        $lazy = LazySeq::defer(function () use (&$evaluated): iterable {
            $evaluated++;
            yield 1;
            yield 2;
            yield 3;
        })->memoize();

        $lazy->toSeq();
        $lazy->toSeq();

        self::assertSame(1, $evaluated);
    }

    public function testZipCombinesElementsFromTwoLazySequences(): void
    {
        $result = LazySeq::from([1, 2, 3])
            ->zip(LazySeq::from(['a', 'b']))
            ->toSeq();

        self::assertSame(
            [[1, 'a'], [2, 'b']],
            $result->toArray(),
        );
    }

    public function testFilterMapIsLazyAndDropsMissingValues(): void
    {
        $result = LazySeq::from([1, 2, 3, 4])
            ->filterMap(
                fn (int $value): Option => $value % 2 === 0
                    ? Option::some($value * 10)
                    : Option::none(),
            )
            ->toSeq();

        self::assertSame([20, 40], $result->toArray());
    }

    public function testSelectProjectsLazyValues(): void
    {
        $result = LazySeq::from([1, 2, 3])
            ->select(static fn (int $value): int => $value * 3)
            ->toSeq();

        self::assertSame([3, 6, 9], $result->toArray());
    }

    public function testScanProducesRunningTotals(): void
    {
        $result = LazySeq::from([1, 2, 3])
            ->scan(0, fn (int $total, int $value): int => $total + $value)
            ->toSeq();

        self::assertSame([0, 1, 3, 6], $result->toArray());
    }

    public function testDistinctByFiltersDuplicateKeys(): void
    {
        $result = LazySeq::from(['apple', 'banana', 'apricot', 'pear'])
            ->distinctBy(fn (string $value): string => substr($value, 0, 1))
            ->toSeq();

        self::assertSame(['apple', 'banana', 'pear'], $result->toArray());
    }

    public function testInnerJoinPairsMatchingRecords(): void
    {
        $result = LazySeq::from([
            ['id' => 1, 'customer_id' => 10],
            ['id' => 2, 'customer_id' => 20],
        ])
            ->innerJoin(
                LazySeq::from([
                    ['id' => 10, 'name' => 'Alice'],
                    ['id' => 20, 'name' => 'Bob'],
                ]),
                static fn (array $order): int => $order['customer_id'],
                static fn (array $customer): int => $customer['id'],
                static fn (array $order, array $customer): array => [
                    'order_id' => $order['id'],
                    'customer_name' => $customer['name'],
                ],
            )
            ->toSeq();

        self::assertSame([
            ['order_id' => 1, 'customer_name' => 'Alice'],
            ['order_id' => 2, 'customer_name' => 'Bob'],
        ], $result->toArray());
    }

    public function testChunkGroupsValuesIntoFixedSizeBuffers(): void
    {
        $result = LazySeq::from([1, 2, 3, 4, 5])
            ->chunk(2)
            ->toSeq();

        self::assertSame(
            [[1, 2], [3, 4], [5]],
            $result->toArray(),
        );
    }

    public function testNegativeTakeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        LazySeq::from([1, 2, 3])->take(-1);
    }
}