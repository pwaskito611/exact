<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Option;

use Exact\Data\Option\None;
use Exact\Data\Option\Option;
use Exact\Data\Option\Some;
use PHPUnit\Framework\TestCase;

final class OptionTest extends TestCase
{
    public function test_some_creates_some(): void
    {
        $option = Option::some('value');

        self::assertInstanceOf(Some::class, $option);
    }

    public function test_none_creates_none(): void
    {
        $option = Option::none();

        self::assertInstanceOf(None::class, $option);
    }

    public function test_from_non_null_value_creates_some(): void
    {
        $option = Option::from('value');

        self::assertInstanceOf(Some::class, $option);
    }

    public function test_from_null_creates_none(): void
    {
        $option = Option::from(null);

        self::assertInstanceOf(None::class, $option);
    }

    public function test_from_accepts_empty_array_as_some(): void
    {
        $option = Option::from([]);

        self::assertInstanceOf(Some::class, $option);
    }

    public function test_some_is_some(): void
    {
        $option = Option::some('value');

        self::assertTrue($option->isSome());
        self::assertFalse($option->isNone());
    }

    public function test_none_is_none(): void
    {
        $option = Option::none();

        self::assertFalse($option->isSome());
        self::assertTrue($option->isNone());
    }
}