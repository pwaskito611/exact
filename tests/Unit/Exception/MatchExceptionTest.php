<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Exception;

use Exact\Exception\MatchException;
use PHPUnit\Framework\TestCase;

final class MatchExceptionTest extends TestCase
{
    public function testNoMatchCreatesMatchException(): void
    {
        $exception = MatchException::noMatch(42);

        self::assertInstanceOf(
            MatchException::class,
            $exception,
        );
    }

    public function testNoMatchContainsValueType(): void
    {
        $exception = MatchException::noMatch(42);

        self::assertStringContainsString(
            'int',
            $exception->getMessage(),
        );
    }

    public function testNoMatchHasExpectedMessage(): void
    {
        $exception = MatchException::noMatch(42);

        self::assertSame(
            'No matching case found for value of type int.',
            $exception->getMessage(),
        );
    }

    public function testNotExhaustiveCreatesMatchException(): void
    {
        $exception = MatchException::notExhaustive(42);

        self::assertInstanceOf(
            MatchException::class,
            $exception,
        );
    }

    public function testNotExhaustiveHasExpectedMessage(): void
    {
        $exception = MatchException::notExhaustive(42);

        self::assertSame(
            'Non-exhaustive match: no case matched value of type int.',
            $exception->getMessage(),
        );
    }

    public function testNoMatchSupportsObjectValues(): void
    {
        $exception = MatchException::noMatch(new \stdClass());

        self::assertStringContainsString(
            'stdClass',
            $exception->getMessage(),
        );
    }

    public function testNoMatchSupportsNull(): void
    {
        $exception = MatchException::noMatch(null);

        self::assertSame(
            'No matching case found for value of type null.',
            $exception->getMessage(),
        );
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = MatchException::noMatch('value');

        self::assertInstanceOf(
            \RuntimeException::class,
            $exception,
        );
    }
}