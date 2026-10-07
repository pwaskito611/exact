<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Type\Either;

use Exact\Type\Either\Either;
use LogicException;
use PHPUnit\Framework\TestCase;
use TypeError;

final class EitherTest extends TestCase
{
    public function testLeftCreatesLeft(): void
    {
        $either = Either::left('error');

        self::assertTrue($either->isLeft());
        self::assertFalse($either->isRight());
        self::assertSame('error', $either->getLeft());
        self::assertSame('error', $either->get());
    }

    public function testRightCreatesRight(): void
    {
        $either = Either::right(100);

        self::assertFalse($either->isLeft());
        self::assertTrue($either->isRight());
        self::assertSame(100, $either->getRight());
        self::assertSame(100, $either->get());
    }

    public function testMapTransformsRight(): void
    {
        $either = Either::right(10);

        $result = $either->map(
            fn (int $value): int => $value * 2
        );

        self::assertTrue($result->isRight());
        self::assertSame(20, $result->getRight());
    }

    public function testMapDoesNotTransformLeft(): void
    {
        $either = Either::left('error');

        $result = $either->map(
            fn (string $value): string => strtoupper($value)
        );

        self::assertTrue($result->isLeft());
        self::assertSame('error', $result->getLeft());
    }

    public function testMapReturnsNewRightWithTransformedValue(): void
    {
        $either = Either::right(10);

        $result = $either->map(
            fn (int $value): int => $value + 5
        );

        self::assertNotSame($either, $result);
        self::assertSame(10, $either->getRight());
        self::assertSame(15, $result->getRight());
    }

    public function testMapLeftTransformsLeft(): void
    {
        $either = Either::left('error');

        $result = $either->mapLeft(
            fn (string $value): string => strtoupper($value)
        );

        self::assertTrue($result->isLeft());
        self::assertSame('ERROR', $result->getLeft());
    }

    public function testMapLeftDoesNotTransformRight(): void
    {
        $either = Either::right(100);

        $result = $either->mapLeft(
            fn (string $value): string => strtoupper($value)
        );

        self::assertTrue($result->isRight());
        self::assertSame(100, $result->getRight());
    }

    public function testFlatMapTransformsRightIntoEither(): void
    {
        $either = Either::right(10);

        $result = $either->flatMap(
            fn (int $value): Either => Either::right($value * 2)
        );

        self::assertTrue($result->isRight());
        self::assertSame(20, $result->getRight());
    }

    public function testFlatMapCanReturnLeft(): void
    {
        $either = Either::right(10);

        $result = $either->flatMap(
            fn (int $value): Either => Either::left('failed')
        );

        self::assertTrue($result->isLeft());
        self::assertSame('failed', $result->getLeft());
    }

    public function testFlatMapDoesNotExecuteOnLeft(): void
    {
        $either = Either::left('error');

        $called = false;

        $result = $either->flatMap(
            function () use (&$called): Either {
                $called = true;

                return Either::right('unexpected');
            }
        );

        self::assertFalse($called);
        self::assertSame($either, $result);
        self::assertTrue($result->isLeft());
        self::assertSame('error', $result->getLeft());
    }

    public function testFlatMapThrowsWhenCallbackDoesNotReturnEither(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(
            'Either::flatMap() callback must return an Either.'
        );

        Either::right(10)->flatMap(
            fn (int $value): int => $value * 2
        );
    }

    public function testFoldCallsOnLeftForLeft(): void
    {
        $either = Either::left('error');

        $result = $either->fold(
            fn (string $error): string => "Failed: {$error}",
            fn (mixed $value): string => 'Success',
        );

        self::assertSame('Failed: error', $result);
    }

    public function testFoldCallsOnRightForRight(): void
    {
        $either = Either::right(100);

        $result = $either->fold(
            fn (mixed $error): string => 'Failed',
            fn (int $value): string => "Success: {$value}",
        );

        self::assertSame('Success: 100', $result);
    }

    public function testFoldDoesNotCallUnusedBranch(): void
    {
        $either = Either::right(100);

        $leftCalled = false;
        $rightCalled = false;

        $result = $either->fold(
            function () use (&$leftCalled): string {
                $leftCalled = true;

                return 'left';
            },
            function (int $value) use (&$rightCalled): string {
                $rightCalled = true;

                return "right: {$value}";
            },
        );

        self::assertFalse($leftCalled);
        self::assertTrue($rightCalled);
        self::assertSame('right: 100', $result);
    }

    public function testGetOrElseReturnsRightValue(): void
    {
        $either = Either::right(100);

        self::assertSame(100, $either->getOrElse(0));
    }

    public function testGetOrElseReturnsDefaultForLeft(): void
    {
        $either = Either::left('error');

        self::assertSame(0, $either->getOrElse(0));
    }

    public function testGetLeftThrowsOnRight(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot get Left value from a Right.'
        );

        Either::right(100)->getLeft();
    }

    public function testGetRightThrowsOnLeft(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot get Right value from a Left.'
        );

        Either::left('error')->getRight();
    }

    public function testGetReturnsLeftValue(): void
    {
        $either = Either::left('error');

        self::assertSame('error', $either->get());
    }

    public function testGetReturnsRightValue(): void
    {
        $either = Either::right(100);

        self::assertSame(100, $either->get());
    }

    public function testSwapConvertsLeftToRight(): void
    {
        $either = Either::left('error');

        $result = $either->swap();

        self::assertTrue($result->isRight());
        self::assertSame('error', $result->getRight());
    }

    public function testSwapConvertsRightToLeft(): void
    {
        $either = Either::right(100);

        $result = $either->swap();

        self::assertTrue($result->isLeft());
        self::assertSame(100, $result->getLeft());
    }

    public function testSwapDoesNotModifyOriginal(): void
    {
        $either = Either::right(100);

        $result = $either->swap();

        self::assertTrue($either->isRight());
        self::assertSame(100, $either->getRight());

        self::assertTrue($result->isLeft());
        self::assertSame(100, $result->getLeft());
    }

    public function testNullCanBeStoredAsLeftValue(): void
    {
        $either = Either::left(null);

        self::assertTrue($either->isLeft());
        self::assertNull($either->getLeft());
        self::assertNull($either->get());
    }

    public function testNullCanBeStoredAsRightValue(): void
    {
        $either = Either::right(null);

        self::assertTrue($either->isRight());
        self::assertNull($either->getRight());
        self::assertNull($either->get());
    }

    public function testMapCanTransformNullRight(): void
    {
        $either = Either::right(null);

        $result = $either->map(
            fn (mixed $value): string => 'value'
        );

        self::assertTrue($result->isRight());
        self::assertSame('value', $result->getRight());
    }

    public function testMapLeftCanTransformNullLeft(): void
    {
        $either = Either::left(null);

        $result = $either->mapLeft(
            fn (mixed $value): string => 'error'
        );

        self::assertTrue($result->isLeft());
        self::assertSame('error', $result->getLeft());
    }

    public function testOfCreatesRightValue(): void
    {
        $either = Either::of(42);

        self::assertTrue($either->isRight());
        self::assertSame(42, $either->getRight());
    }

    public function testPureCreatesRightValue(): void
    {
        $either = Either::pure('done');

        self::assertTrue($either->isRight());
        self::assertSame('done', $either->getRight());
    }

    public function testFromNullableCreatesLeftForNull(): void
    {
        $either = Either::fromNullable(null, 'missing');

        self::assertTrue($either->isLeft());
        self::assertSame('missing', $either->getLeft());
    }

    public function testTryCatchReturnsLeftForThrownException(): void
    {
        $either = Either::tryCatch(
            fn (): int => throw new \RuntimeException('bad'),
            fn (\Throwable $exception): string => 'caught: ' . $exception->getMessage(),
        );

        self::assertTrue($either->isLeft());
        self::assertSame('caught: bad', $either->getLeft());
    }

    public function testBimapTransformsTheMatchingBranch(): void
    {
        $left = Either::left('error');
        $right = Either::right(10);

        self::assertSame('ERROR', $left->bimap(
            fn (string $value): string => strtoupper($value),
            fn (int $value): int => $value + 1,
        )->getLeft());

        self::assertSame(11, $right->bimap(
            fn (string $value): string => strtoupper($value),
            fn (int $value): int => $value + 1,
        )->getRight());
    }

    public function testFlatMapLeftTransformsLeft(): void
    {
        $either = Either::left('error');

        $result = $either->flatMapLeft(
            fn (string $value): Either => Either::left(strtoupper($value))
        );

        self::assertTrue($result->isLeft());
        self::assertSame('ERROR', $result->getLeft());
    }

    public function testFlatMapLeftDoesNotExecuteOnRight(): void
    {
        $either = Either::right(5);
        $called = false;

        $result = $either->flatMapLeft(
            function () use (&$called): Either {
                $called = true;

                return Either::left('ignored');
            }
        );

        self::assertFalse($called);
        self::assertSame($either, $result);
    }

    public function testOrElseGetIsLazyAndValidatesResult(): void
    {
        $either = Either::left('error');
        $count = 0;

        $result = $either->orElseGet(function () use (&$count): Either {
            $count++;

            return Either::right('recovered');
        });

        self::assertSame('recovered', $result->getRight());
        self::assertSame(1, $count);

        $this->expectException(TypeError::class);
        Either::left('oops')->orElseGet(fn (): string => 'bad');
    }

    public function testGetOrElseGetIsLazyAndGetOrElseThrowThrows(): void
    {
        $right = Either::right(8);
        $left = Either::left('error');

        self::assertSame(8, $right->getOrElseGet(fn (): int => 99));
        self::assertSame(99, $left->getOrElseGet(fn (): int => 99));

        self::assertSame(8, $right->getOrElseThrow(new \RuntimeException('bad')));

        $this->expectException(\RuntimeException::class);
        $left->getOrElseThrow(new \RuntimeException('bad'));
    }

    public function testFilterExistsForallAndContains(): void
    {
        self::assertTrue(Either::right(5)->filter(fn (int $value): bool => $value > 3)->isRight());
        self::assertTrue(Either::right(2)->filter(fn (int $value): bool => $value > 3, 'nope')->isLeft());
        self::assertTrue(Either::right(5)->exists(fn (int $value): bool => $value > 3));
        self::assertFalse(Either::left('x')->exists(fn (string $value): bool => $value === 'x'));
        self::assertTrue(Either::left('x')->forall(fn (string $value): bool => $value === 'x'));
        self::assertTrue(Either::right('x')->contains('x'));
        self::assertFalse(Either::right('x')->contains('y'));
    }

    public function testTapAndTapLeftUseTheCurrentBranchOnly(): void
    {
        $calls = [];

        $right = Either::right(3)->tap(function (int $value) use (&$calls): void {
            $calls[] = "right:$value";
        });

        $left = Either::left('error')->tapLeft(function (string $value) use (&$calls): void {
            $calls[] = "left:$value";
        });

        self::assertSame(3, $right->getRight());
        self::assertSame('error', $left->getLeft());
        self::assertSame(['right:3', 'left:error'], $calls);
    }

    public function testApZipWithZipAndFlatten(): void
    {
        $ap = Either::right(fn (int $value): int => $value * 2)->ap(Either::right(10));
        self::assertSame(20, $ap->getRight());

        $zip = Either::right('a')->zipWith(Either::right('b'), fn (string $left, string $right): string => $left . $right);
        self::assertSame('ab', $zip->getRight());

        $zipped = Either::right('x')->zip(Either::right('y'));
        self::assertSame(['x', 'y'], $zipped->getRight());

        $nested = Either::right(Either::right(9));
        self::assertSame(9, $nested->flatten()->getRight());

        $this->expectException(TypeError::class);
        Either::right(9)->flatten();
    }

    public function testSequenceAndTraverseFailFastAndAccumulateResults(): void
    {
        $sequence = Either::sequence([
            Either::right('a'),
            Either::right('b'),
        ]);
        self::assertSame(['a', 'b'], $sequence->getRight());

        $left = Either::sequence([
            Either::right('a'),
            Either::left('boom'),
        ]);
        self::assertTrue($left->isLeft());
        self::assertSame('boom', $left->getLeft());

        $traverse = Either::traverse(['a', 'b'], fn (string $value): Either => Either::right(strlen($value)));
        self::assertSame([1, 1], $traverse->getRight());

        $traversedLeft = Either::traverse(['a', 'b'], function (string $value) {
            return $value === 'b'
                ? Either::left('fail')
                : Either::right($value);
        });
        self::assertTrue($traversedLeft->isLeft());
        self::assertSame('fail', $traversedLeft->getLeft());
    }
}