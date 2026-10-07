<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Pattern;

use Exact\Pattern\Matcher;
use Exact\Pattern\Variant;
use Exact\Exception\MatchException;
use PHPUnit\Framework\TestCase;

final class MatcherTest extends TestCase
{
    public function testCaseMatchesVariant(): void
    {
        $variant = Variant::of('Success', 100);

        $result = (new Matcher($variant))
            ->case(
                'Success',
                fn (Variant $value) => $value->value() * 2,
            )
            ->run();

        self::assertSame(200, $result);
    }

    public function testUnmatchedCaseIsIgnoredWhenAnotherCaseMatches(): void
    {
        $variant = Variant::of('Success', 100);

        $result = (new Matcher($variant))
            ->case(
                'Error',
                fn (Variant $variant) => $variant->name(),
            )
            ->case(
                'Success',
                fn (Variant $variant) => $variant->value(),
            )
            ->run();

        self::assertSame(100, $result);
    }

    public function testDefaultHandlesUnmatchedVariant(): void
    {
        $variant = Variant::of('Unknown', 100);

        $result = (new Matcher($variant))
            ->case(
                'Success',
                fn (Variant $variant) => $variant->value(),
            )
            ->default(
                fn (Variant $variant): string =>
                    "{$variant->name()}: {$variant->value()}",
            )
            ->run();

        self::assertSame('Unknown: 100', $result);
    }

    public function testDefaultReceivesVariantValueAndName(): void
    {
        $variant = Variant::of('Error', 'Database failed');

        $result = (new Matcher($variant))
            ->default(
                fn (Variant $variant): string =>
                    "{$variant->name()}: {$variant->value()}",
            )
            ->run();

        self::assertSame(
            'Error: Database failed',
            $result,
        );
    }

    public function testThrowsMatchExceptionWhenNoCaseMatches(): void
    {
        $variant = Variant::of('Unknown', 100);

        $this->expectException(MatchException::class);

        (new Matcher($variant))
            ->case(
                'Success',
                fn (Variant $variant) => $variant->value(),
            )
            ->run();
    }

    public function testCaseReturnsMatcher(): void
    {
        $matcher = new Matcher(
            Variant::of('Success', 100),
        );

        self::assertSame(
            $matcher,
            $matcher->case(
                'Success',
                fn (Variant $variant) => $variant->value(),
            ),
        );
    }

    public function testDefaultReturnsMatcher(): void
    {
        $matcher = new Matcher(
            Variant::of('Success', 100),
        );

        self::assertSame(
            $matcher,
            $matcher->default(
                fn (Variant $variant) => $variant->name(),
            ),
        );
    }

    public function testMatchingCaseTakesPrecedenceOverDefault(): void
    {
        $variant = Variant::of('Success', 100);

        $result = (new Matcher($variant))
            ->case(
                'Success',
                fn (Variant $variant) => $variant->value() * 2,
            )
            ->default(
                fn (Variant $variant) => $variant->value(),
            )
            ->run();

        self::assertSame(200, $result);
    }

    public function testEarlierCaseForSameNameTakesPrecedence(): void
    {
        $variant = Variant::of('Success', 100);

        $result = (new Matcher($variant))
            ->case(
                'Success',
                fn () => 1,
            )
            ->case(
                'Success',
                fn () => 2,
            )
            ->run();

        self::assertSame(1, $result);
    }
}