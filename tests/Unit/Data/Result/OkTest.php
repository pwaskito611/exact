<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Result;

use Exact\Data\Result\Err;
use Exact\Data\Result\Ok;
use Exact\Data\Result\Result;
use LogicException;
use PHPUnit\Framework\TestCase;

final class OkTest extends TestCase
{
    public function testIsOkReturnsTrue(): void
    {
        $result = Result::ok(100);

        self::assertTrue($result->isOk());
    }

    public function testIsErrReturnsFalse(): void
    {
        $result = Result::ok(100);

        self::assertFalse($result->isErr());
    }

    public function testGetReturnsValue(): void
    {
        $result = Result::ok(100);

        self::assertSame(100, $result->get());
    }

    public function testGetOrElseReturnsValue(): void
    {
        $result = Result::ok(100);

        self::assertSame(
            100,
            $result->getOrElse(0),
        );
    }

    public function testMapTransformsValue(): void
    {
        $result = Result::ok(100)
            ->map(fn (int $value) => $value * 2);

        self::assertInstanceOf(Ok::class, $result);
        self::assertSame(200, $result->get());
    }

    public function testMapDoesNotChangeResultVariant(): void
    {
        $result = Result::ok(100)
            ->map(fn (int $value) => $value * 2);

        self::assertTrue($result->isOk());
        self::assertFalse($result->isErr());
    }

    public function testMapErrDoesNothing(): void
    {
        $result = Result::ok(100)
            ->mapErr(fn (string $error) => strtoupper($error));

        self::assertSame(100, $result->get());
        self::assertTrue($result->isOk());
    }

    public function testFlatMapReturnsResult(): void
    {
        $result = Result::ok(100)
            ->flatMap(
                fn (int $value) => Result::ok($value * 2),
            );

        self::assertSame(200, $result->get());
    }

    public function testFlatMapCanReturnErr(): void
    {
        $result = Result::ok(100)
            ->flatMap(
                fn () => Result::err('Failed'),
            );

        self::assertTrue($result->isErr());
        self::assertSame('Failed', $result->error());
    }

    public function testFlatMapRejectsInvalidReturnValue(): void
    {
        $this->expectException(\TypeError::class);

        Result::ok(100)->flatMap(
            fn () => 123,
        );
    }

    public function testFoldCallsOnOk(): void
    {
        $result = Result::ok(100);

        $value = $result->fold(
            fn () => 'error',
            fn (int $value) => $value * 2,
        );

        self::assertSame(200, $value);
    }

    public function testFoldDoesNotCallOnErr(): void
    {
        $result = Result::ok(100);

        $value = $result->fold(
            fn () => throw new \RuntimeException(
                'onErr should not be called'
            ),
            fn (int $value) => $value,
        );

        self::assertSame(100, $value);
    }

    public function testErrorThrowsLogicException(): void
    {
        $this->expectException(LogicException::class);

        Result::ok(100)->error();
    }
}