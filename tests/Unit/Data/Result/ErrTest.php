<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Result;

use Exact\Data\Result\Err;
use Exact\Data\Result\Result;
use LogicException;
use PHPUnit\Framework\TestCase;

final class ErrTest extends TestCase
{
    public function testIsOkReturnsFalse(): void
    {
        $result = Result::err('Failed');

        self::assertFalse($result->isOk());
    }

    public function testIsErrReturnsTrue(): void
    {
        $result = Result::err('Failed');

        self::assertTrue($result->isErr());
    }

    public function testErrorReturnsError(): void
    {
        $result = Result::err('Database failed');

        self::assertSame(
            'Database failed',
            $result->error(),
        );
    }

    public function testGetOrElseReturnsDefault(): void
    {
        $result = Result::err('Failed');

        self::assertSame(
            0,
            $result->getOrElse(0),
        );
    }

    public function testMapDoesNothing(): void
    {
        $result = Result::err('Failed')
            ->map(fn (int $value) => $value * 2);

        self::assertTrue($result->isErr());
        self::assertSame('Failed', $result->error());
    }

    public function testMapErrTransformsError(): void
    {
        $result = Result::err('failed')
            ->mapErr(
                fn (string $error) => strtoupper($error),
            );

        self::assertSame(
            'FAILED',
            $result->error(),
        );
    }

    public function testFlatMapDoesNothing(): void
    {
        $result = Result::err('Failed')
            ->flatMap(
                fn (int $value) => Result::ok($value * 2),
            );

        self::assertTrue($result->isErr());
        self::assertSame('Failed', $result->error());
    }

    public function testFoldCallsOnErr(): void
    {
        $result = Result::err('Failed');

        $value = $result->fold(
            fn (string $error) => 'Error: ' . $error,
            fn () => 'Success',
        );

        self::assertSame(
            'Error: Failed',
            $value,
        );
    }

    public function testFoldDoesNotCallOnOk(): void
    {
        $result = Result::err('Failed');

        $value = $result->fold(
            fn () => 'Error',
            fn () => throw new \RuntimeException(
                'onOk should not be called'
            ),
        );

        self::assertSame('Error', $value);
    }

    public function testGetThrowsLogicException(): void
    {
        $this->expectException(LogicException::class);

        Result::err('Failed')->get();
    }

    public function testMapErrReturnsNewResult(): void
    {
        $result = Result::err('failed');

        $mapped = $result->mapErr(
            fn (string $error) => strtoupper($error),
        );

        self::assertNotSame($result, $mapped);
        self::assertSame('FAILED', $mapped->error());
    }
}