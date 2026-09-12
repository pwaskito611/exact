<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Exception;

use Exact\Exception\InvalidStateException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class InvalidStateExceptionTest extends TestCase
{
    public function test_it_extends_runtime_exception(): void
    {
        $exception = new InvalidStateException();

        self::assertInstanceOf(RuntimeException::class, $exception);
    }

    public function test_it_has_default_message(): void
    {
        $exception = new InvalidStateException();

        self::assertSame(
            'The object is in an invalid state.',
            $exception->getMessage()
        );
    }

    public function test_it_accepts_custom_message(): void
    {
        $message = 'The collection is already consumed.';

        $exception = new InvalidStateException($message);

        self::assertSame($message, $exception->getMessage());
    }
}