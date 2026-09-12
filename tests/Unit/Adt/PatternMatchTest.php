<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Adt;

use Exact\Adt\Matcher;
use Exact\Adt\PatternMatch;
use Exact\Adt\Variant;
use PHPUnit\Framework\TestCase;

final class PatternMatchTest extends TestCase
{
    public function testOnReturnsMatcher(): void
    {
        $variant = Variant::of('Success', 100);

        $matcher = PatternMatch::on($variant);

        self::assertInstanceOf(Matcher::class, $matcher);
    }

    public function testOnPreservesVariant(): void
    {
        $result = PatternMatch::on(
            Variant::of('Success', 100),
        )
            ->case(
                'Success',
                fn (int $value) => $value * 2,
            )
            ->run();

        self::assertSame(200, $result);
    }

    public function testCanChainMultipleCases(): void
    {
        $result = PatternMatch::on(
            Variant::of('Error', 'Database failed'),
        )
            ->case(
                'Success',
                fn (int $value) => $value,
            )
            ->case(
                'Error',
                fn (string $error) => $error,
            )
            ->run();

        self::assertSame(
            'Database failed',
            $result,
        );
    }

    public function testCanUseDefault(): void
    {
        $result = PatternMatch::on(
            Variant::of('Unknown', 123),
        )
            ->case(
                'Success',
                fn (int $value) => $value,
            )
            ->default(
                fn (mixed $value, string $name) => $name,
            )
            ->run();

        self::assertSame('Unknown', $result);
    }
}