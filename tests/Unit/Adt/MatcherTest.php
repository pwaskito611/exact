<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Adt;

use Exact\Adt\Matcher;
use Exact\Adt\Variant;
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
                fn (int $value) => $value * 2,
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
                fn () => 'error',
            )
            ->case(
                'Success',
                fn (int $value) => $value,
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
                fn (int $value) => $value,
            )
            ->default(
                fn (mixed $value, string $name) =>
                    "{$name}: {$value}",
            )
            ->run();

        self::assertSame('Unknown: 100', $result);
    }

    public function testDefaultReceivesVariantValueAndName(): void
    {
        $variant = Variant::of('Error', 'Database failed');

        $result = (new Matcher($variant))
            ->default(
                function (mixed $value, string $name): string {
                    return $name . ': ' . $value;
                },
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
                fn (int $value) => $value,
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
                fn (int $value) => $value,
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
                fn () => null,
            ),
        );
    }

    public function testMatchingCaseTakesPrecedenceOverDefault(): void
    {
        $variant = Variant::of('Success', 100);

        $result = (new Matcher($variant))
            ->case(
                'Success',
                fn (int $value) => $value * 2,
            )
            ->default(
                fn () => 0,
            )
            ->run();

        self::assertSame(200, $result);
    }

    public function testLastCaseForSameNameReplacesPreviousCase(): void
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

        self::assertSame(2, $result);
    }
}