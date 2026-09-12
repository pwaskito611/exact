<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Option;

use Exact\Data\Option\None;
use Exact\Data\Option\Option;
use Exact\Data\Option\Some;
use PHPUnit\Framework\TestCase;

final class NoneTest extends TestCase
{
    public function test_is_none(): void
    {
        $none = new None();

        self::assertFalse($none->isSome());
        self::assertTrue($none->isNone());
    }

    public function test_map_does_not_execute_callback(): void
    {
        $none = new None();

        $called = false;

        $result = $none->map(
            function () use (&$called): string {
                $called = true;

                return 'value';
            }
        );

        self::assertFalse($called);
        self::assertSame($none, $result);
    }

    public function test_flat_map_does_not_execute_callback(): void
    {
        $none = new None();

        $called = false;

        $result = $none->flatMap(
            function () use (&$called): Option {
                $called = true;

                return Option::some('value');
            }
        );

        self::assertFalse($called);
        self::assertSame($none, $result);
    }

    public function test_filter_does_not_execute_predicate(): void
    {
        $none = new None();

        $called = false;

        $result = $none->filter(
            function () use (&$called): bool {
                $called = true;

                return true;
            }
        );

        self::assertFalse($called);
        self::assertSame($none, $result);
    }

    public function test_fold_calls_on_none(): void
    {
        $none = new None();

        $result = $none->fold(
            fn (): string => 'No value',
            fn (): string => 'Has value',
        );

        self::assertSame('No value', $result);
    }

    public function test_get_or_else_returns_default(): void
    {
        $none = new None();

        self::assertSame(
            'default',
            $none->getOrElse('default')
        );
    }

    public function test_or_else_returns_default_option(): void
    {
        $none = new None();
        $default = Option::some('default');

        $result = $none->orElse($default);

        self::assertSame($default, $result);
    }

    public function test_it_is_immutable(): void
    {
        $reflection = new \ReflectionClass(None::class);

        self::assertTrue($reflection->isReadOnly());
    }
}