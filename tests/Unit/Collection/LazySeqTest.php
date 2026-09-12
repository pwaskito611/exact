<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Collection;

use Exact\Collection\LazySeq;
use Exact\Collection\Seq;
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

        $result = $lazy->toList();

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

        $lazy->toList();

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

        $lazy->toList();

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

        $lazy->toList();

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
            ->toList();

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
            ->toList();

        self::assertSame([], $result->toArray());
        self::assertFalse($evaluated);
    }

    public function testMapAndFilterCanBeComposed(): void
    {
        $result = LazySeq::from([1, 2, 3, 4, 5])
            ->map(fn (int $value) => $value * 2)
            ->filter(fn (int $value) => $value > 5)
            ->toList();

        self::assertInstanceOf(Seq::class, $result);

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
            ->toList();

        self::assertSame(
            [4, 8, 12, 16, 20],
            $result->toArray(),
        );
    }

    public function testNegativeTakeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        LazySeq::from([1, 2, 3])->take(-1);
    }
}