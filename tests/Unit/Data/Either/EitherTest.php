<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Either;

use Exact\Data\Either\Either;
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
}