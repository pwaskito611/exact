<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Adt;

use Exact\Adt\Variant;
use PHPUnit\Framework\TestCase;

final class VariantTest extends TestCase
{
    public function testOfCreatesVariant(): void
    {
        $variant = Variant::of('Success', 100);

        self::assertInstanceOf(Variant::class, $variant);
    }

    public function testNameReturnsVariantName(): void
    {
        $variant = Variant::of('Success', 100);

        self::assertSame('Success', $variant->name());
    }

    public function testValueReturnsVariantValue(): void
    {
        $value = ['id' => 1];

        $variant = Variant::of('Success', $value);

        self::assertSame($value, $variant->value());
    }

    public function testValueDefaultsToNull(): void
    {
        $variant = Variant::of('None');

        self::assertNull($variant->value());
    }

    public function testIsReturnsTrueForMatchingName(): void
    {
        $variant = Variant::of('Success', 100);

        self::assertTrue($variant->is('Success'));
    }

    public function testIsReturnsFalseForDifferentName(): void
    {
        $variant = Variant::of('Success', 100);

        self::assertFalse($variant->is('Error'));
    }
}