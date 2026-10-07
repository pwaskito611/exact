<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Pattern;

use Exact\Pattern\Matcher;
use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;
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
                fn (Variant $variant) => $variant->value() * 2,
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
                fn (Variant $variant) => $variant->value(),
            )
            ->case(
                'Error',
                fn (Variant $variant) => $variant->value(),
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
                fn (Variant $variant) => $variant->value(),
            )
            ->default(
                fn (Variant $variant) => $variant->name(),
            )
            ->run();

        self::assertSame('Unknown', $result);
    }
}