<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Validated;

use Exact\Data\Validated\Invalid;
use Exact\Data\Validated\Valid;
use Exact\Data\Validated\Validated;
use LogicException;
use PHPUnit\Framework\TestCase;

final class ValidatedTest extends TestCase
{
    public function testValidCreatesValid(): void
    {
        $validated = Validated::valid(100);

        self::assertInstanceOf(Valid::class, $validated);
        self::assertTrue($validated->isValid());
        self::assertFalse($validated->isInvalid());
        self::assertSame(100, $validated->getValid());
        self::assertSame(100, $validated->get());
    }

    public function testInvalidCreatesInvalid(): void
    {
        $validated = Validated::invalid('Invalid value');

        self::assertInstanceOf(Invalid::class, $validated);
        self::assertFalse($validated->isValid());
        self::assertTrue($validated->isInvalid());
        self::assertSame('Invalid value', $validated->getInvalid());
        self::assertSame('Invalid value', $validated->get());
    }

    public function testMapTransformsValid(): void
    {
        $validated = Validated::valid(10);

        $result = $validated->map(
            fn (int $value): int => $value * 2
        );

        self::assertTrue($result->isValid());
        self::assertSame(20, $result->getValid());
    }

    public function testMapDoesNotTransformInvalid(): void
    {
        $validated = Validated::invalid('error');

        $result = $validated->map(
            fn (string $value): string => strtoupper($value)
        );

        self::assertTrue($result->isInvalid());
        self::assertSame('error', $result->getInvalid());
    }

    public function testMapInvalidTransformsInvalid(): void
    {
        $validated = Validated::invalid('invalid');

        $result = $validated->mapInvalid(
            fn (string $error): string => strtoupper($error)
        );

        self::assertTrue($result->isInvalid());
        self::assertSame('INVALID', $result->getInvalid());
    }

    public function testMapInvalidDoesNotTransformValid(): void
    {
        $validated = Validated::valid(100);

        $result = $validated->mapInvalid(
            fn (string $error): string => strtoupper($error)
        );

        self::assertTrue($result->isValid());
        self::assertSame(100, $result->getValid());
    }

    public function testMapPreservesInvalidInstance(): void
    {
        $validated = Validated::invalid('error');

        $result = $validated->map(
            fn (mixed $value): mixed => $value
        );

        self::assertSame($validated, $result);
    }

    public function testMapInvalidPreservesValidInstance(): void
    {
        $validated = Validated::valid(100);

        $result = $validated->mapInvalid(
            fn (mixed $error): mixed => $error
        );

        self::assertSame($validated, $result);
    }

    public function testFoldCallsValidCallbackForValid(): void
    {
        $validated = Validated::valid(100);

        $result = $validated->fold(
            fn (mixed $error): string => 'invalid',
            fn (int $value): string => "valid: {$value}",
        );

        self::assertSame('valid: 100', $result);
    }

    public function testFoldCallsInvalidCallbackForInvalid(): void
    {
        $validated = Validated::invalid('error');

        $result = $validated->fold(
            fn (string $error): string => "invalid: {$error}",
            fn (mixed $value): string => 'valid',
        );

        self::assertSame('invalid: error', $result);
    }

    public function testFoldOnlyCallsSelectedBranch(): void
    {
        $validated = Validated::valid(100);

        $invalidCalled = false;
        $validCalled = false;

        $validated->fold(
            function () use (&$invalidCalled): string {
                $invalidCalled = true;

                return 'invalid';
            },
            function (int $value) use (&$validCalled): string {
                $validCalled = true;

                return "valid: {$value}";
            },
        );

        self::assertFalse($invalidCalled);
        self::assertTrue($validCalled);
    }

    public function testGetOrElseReturnsValidValue(): void
    {
        $validated = Validated::valid(100);

        self::assertSame(100, $validated->getOrElse(0));
    }

    public function testGetOrElseReturnsDefaultForInvalid(): void
    {
        $validated = Validated::invalid('error');

        self::assertSame(0, $validated->getOrElse(0));
    }

    public function testGetValidThrowsForInvalid(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot get Valid value from an Invalid.'
        );

        Validated::invalid('error')->getValid();
    }

    public function testGetInvalidThrowsForValid(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot get Invalid value from a Valid.'
        );

        Validated::valid(100)->getInvalid();
    }

    public function testGetReturnsValidValue(): void
    {
        self::assertSame(
            100,
            Validated::valid(100)->get()
        );
    }

    public function testGetReturnsInvalidValue(): void
    {
        self::assertSame(
            'error',
            Validated::invalid('error')->get()
        );
    }

    public function testCombineTwoValidValues(): void
    {
        $first = Validated::valid(10);
        $second = Validated::valid(20);

        $result = $first->combine(
            $second,
            fn (int $a, int $b): int => $a + $b,
            fn ($a, $b) => [$a, $b],
        );

        self::assertTrue($result->isValid());
        self::assertSame(30, $result->getValid());
    }

    public function testCombineTwoInvalidValues(): void
    {
        $first = Validated::invalid(['name is required']);
        $second = Validated::invalid(['email is invalid']);

        $result = $first->combine(
            $second,
            fn ($a, $b) => [$a, $b],
            fn (array $a, array $b): array => [
                ...$a,
                ...$b,
            ],
        );

        self::assertTrue($result->isInvalid());

        self::assertSame(
            [
                'name is required',
                'email is invalid',
            ],
            $result->getInvalid()
        );
    }

    public function testCombineValidAndInvalidReturnsInvalid(): void
    {
        $first = Validated::valid(10);
        $second = Validated::invalid('error');

        $result = $first->combine(
            $second,
            fn ($a, $b) => $a + $b,
            fn ($a, $b) => [$a, $b],
        );

        self::assertTrue($result->isInvalid());
        self::assertSame('error', $result->getInvalid());
    }

    public function testCombineInvalidAndValidReturnsInvalid(): void
    {
        $first = Validated::invalid('error');
        $second = Validated::valid(10);

        $result = $first->combine(
            $second,
            fn ($a, $b) => $a + $b,
            fn ($a, $b) => [$a, $b],
        );

        self::assertTrue($result->isInvalid());
        self::assertSame('error', $result->getInvalid());
    }

    public function testCombineDoesNotCallValidCombinerWhenInvalid(): void
    {
        $first = Validated::invalid('first');
        $second = Validated::invalid('second');

        $validCalled = false;
        $invalidCalled = false;

        $first->combine(
            $second,
            function () use (&$validCalled): mixed {
                $validCalled = true;

                return null;
            },
            function ($a, $b) use (&$invalidCalled): array {
                $invalidCalled = true;

                return [$a, $b];
            },
        );

        self::assertFalse($validCalled);
        self::assertTrue($invalidCalled);
    }

    public function testCombineDoesNotCallInvalidCombinerWhenValid(): void
    {
        $first = Validated::valid(10);
        $second = Validated::valid(20);

        $validCalled = false;
        $invalidCalled = false;

        $first->combine(
            $second,
            function ($a, $b) use (&$validCalled): int {
                $validCalled = true;

                return $a + $b;
            },
            function () use (&$invalidCalled): mixed {
                $invalidCalled = true;

                return null;
            },
        );

        self::assertTrue($validCalled);
        self::assertFalse($invalidCalled);
    }

    public function testNullCanBeValidValue(): void
    {
        $validated = Validated::valid(null);

        self::assertTrue($validated->isValid());
        self::assertNull($validated->getValid());
    }

    public function testNullCanBeInvalidValue(): void
    {
        $validated = Validated::invalid(null);

        self::assertTrue($validated->isInvalid());
        self::assertNull($validated->getInvalid());
    }
}