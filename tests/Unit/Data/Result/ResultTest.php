<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Result;

use Exact\Data\Result\Err;
use Exact\Data\Result\Ok;
use Exact\Data\Result\Result;
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase
{
    public function testOkCreatesOkResult(): void
    {
        $result = Result::ok(100);

        self::assertInstanceOf(Ok::class, $result);
        self::assertTrue($result->isOk());
        self::assertFalse($result->isErr());
    }

    public function testErrCreatesErrResult(): void
    {
        $result = Result::err('Something went wrong');

        self::assertInstanceOf(Err::class, $result);
        self::assertFalse($result->isOk());
        self::assertTrue($result->isErr());
    }

    public function testOkCanContainNull(): void
    {
        $result = Result::ok(null);

        self::assertTrue($result->isOk());
        self::assertNull($result->get());
    }

    public function testErrCanContainAnyErrorValue(): void
    {
        $error = [
            'code' => 500,
            'message' => 'Internal error',
        ];

        $result = Result::err($error);

        self::assertSame($error, $result->error());
    }
}