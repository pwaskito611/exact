<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Exception;

use Exact\Exception\EmptyCollectionException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EmptyCollectionExceptionTest extends TestCase
{
    public function test_it_extends_runtime_exception(): void
    {
        $exception = new EmptyCollectionException();

        self::assertInstanceOf(RuntimeException::class, $exception);
    }

    public function test_it_has_default_message(): void
    {
        $exception = new EmptyCollectionException();

        self::assertSame(
            'Cannot perform this operation on an empty collection.',
            $exception->getMessage()
        );
    }

    public function test_it_accepts_custom_message(): void
    {
        $message = 'Cannot get head of an empty list.';

        $exception = new EmptyCollectionException($message);

        self::assertSame($message, $exception->getMessage());
    }
}