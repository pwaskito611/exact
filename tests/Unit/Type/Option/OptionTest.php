<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Type\Option;

use Exact\Type\Either\Either;
use Exact\Type\Option\None;
use Exact\Type\Option\Option;
use Exact\Type\Option\Some;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

final class OptionTest extends TestCase
{
    public function test_some_creates_some(): void
    {
        $option = Option::some('value');

        self::assertInstanceOf(Some::class, $option);
        self::assertSame('value', $option->value());
    }

    public function test_none_creates_none(): void
    {
        $option = Option::none();

        self::assertInstanceOf(None::class, $option);
        self::assertInstanceOf(None::class, Option::none());
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

    public function test_when_creates_some_or_none_based_on_condition(): void
    {
        self::assertTrue(Option::when(true, 'value')->isSome());
        self::assertTrue(Option::when(false, 'value')->isNone());
    }

    public function test_try_catch_returns_some_on_success_and_none_on_exception(): void
    {
        self::assertSame('ok', Option::tryCatch(static fn (): string => 'ok')->getOrElse('fallback'));
        self::assertSame('fallback', Option::tryCatch(static fn (): string => throw new RuntimeException('boom'))->getOrElse('fallback'));
    }

    public function test_option_types_are_readonly_and_value_or_fail_is_internal(): void
    {
        self::assertTrue((new \ReflectionClass(\Exact\Type\Option\Some::class))->isReadOnly());
        self::assertTrue((new \ReflectionClass(\Exact\Type\Option\None::class))->isReadOnly());

        $method = new \ReflectionMethod(\Exact\Type\Option\Some::class, 'valueOrFail');
        self::assertTrue($method->isProtected());

        $someMethod = new \ReflectionMethod(\Exact\Type\Option\Some::class, 'valueOrFail');
        $someMethod->setAccessible(true);
        $someValue = $someMethod->invoke(Option::some(42));
        self::assertSame(42, $someValue);

        $noneMethod = new \ReflectionMethod(\Exact\Type\Option\None::class, 'valueOrFail');
        $noneMethod->setAccessible(true);

        $this->expectException(\LogicException::class);
        $noneMethod->invoke(Option::none());
    }

    public function test_first_some_returns_first_present_value(): void
    {
        $option = Option::firstSome([
            Option::none(),
            Option::some('first'),
            Option::some('second'),
        ]);

        self::assertSame('first', $option->value());
    }

    public function test_sequence_all_some_returns_some_list(): void
    {
        $option = Option::sequence([
            Option::some('a'),
            Option::some('b'),
            Option::some('c'),
        ]);

        self::assertSame(['a', 'b', 'c'], $option->getOrElse([]));
    }

    public function test_sequence_with_none_returns_none(): void
    {
        self::assertTrue(Option::sequence([
            Option::some('a'),
            Option::none(),
            Option::some('b'),
        ])->isNone());
    }

    public function test_traverse_collect_and_flatten_work(): void
    {
        $traversed = Option::traverse(['a', 'b'], static fn (string $value): Option => Option::some(strlen($value)));
        self::assertSame([1, 1], $traversed->getOrElse([]));

        $collected = Option::collect([
            Option::some('a'),
            Option::none(),
            Option::some('b'),
        ]);
        self::assertSame(['a', 'b'], $collected);

        self::assertSame('x', Option::some(Option::some('x'))->flatten()->getOrElse('fallback'));
    }

    public function test_flat_map_guards_the_callback_return_type(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Some::flatMap() callback must return an Option.');

        Option::some(10)->flatMap(static fn (int $value): int => $value * 2);
    }

    public function test_getters_and_recovery_methods_are_lazy_and_safe(): void
    {
        self::assertSame('value', Option::some('value')->getOrElse('fallback'));
        self::assertSame('fallback', Option::none()->getOrElse('fallback'));

        $count = 0;
        self::assertSame('value', Option::some('value')->getOrElseGet(static function () use (&$count): string {
            $count++;

            return 'fallback';
        }));
        self::assertSame(0, $count);

        self::assertSame('fallback', Option::none()->getOrElseGet(static function () use (&$count): string {
            $count++;

            return 'fallback';
        }));
        self::assertSame(1, $count);

        $this->expectException(RuntimeException::class);
        Option::none()->getOrElseThrow(new RuntimeException('missing'));
    }

    public function test_get_or_null_and_or_else_values(): void
    {
        self::assertSame('value', Option::some('value')->getOrNull());
        self::assertNull(Option::none()->getOrNull());

        self::assertSame('value', Option::some('value')->orElse(Option::some('fallback'))->getOrElse('missing'));
        self::assertSame('fallback', Option::none()->orElse(Option::some('fallback'))->getOrElse('missing'));
    }

    public function test_predicates_and_contains_work_as_expected(): void
    {
        self::assertTrue(Option::some(5)->exists(static fn (int $value): bool => $value > 3));
        self::assertFalse(Option::none()->exists(static fn (int $value): bool => $value > 3));
        self::assertTrue(Option::some(5)->forall(static fn (int $value): bool => $value > 3));
        self::assertTrue(Option::none()->forall(static fn (int $value): bool => $value > 3));
        self::assertTrue(Option::some('x')->contains('x'));
        self::assertFalse(Option::none()->contains('x'));
    }

    public function test_equals_and_tap_are_branch_aware(): void
    {
        self::assertTrue(Option::some(1)->equals(Option::some(1)));
        self::assertFalse(Option::some(1)->equals(Option::some(2)));
        self::assertFalse(Option::some(1)->equals(Option::none()));
        self::assertTrue(Option::none()->equals(Option::none()));

        $calls = [];
        Option::some('abc')->tap(static function (string $value) use (&$calls): void {
            $calls[] = strtoupper($value);
        });
        self::assertSame(['ABC'], $calls);
    }

    public function test_zip_ap_to_either_and_json_serialization_work(): void
    {
        $zip = Option::some('a')->zipWith(Option::some('b'), static fn (string $left, string $right): string => $left . $right);
        self::assertSame('ab', $zip->getOrElse(''));

        $ap = Option::some(static fn (int $value): int => $value * 2)->ap(Option::some(5));
        self::assertSame(10, $ap->getOrElse(0));

        self::assertTrue(Option::some('x')->toEither('missing')->isRight());
        self::assertTrue(Option::none()->toEither('missing')->isLeft());
        self::assertTrue(Either::right('value')->toOption()->isSome());
        self::assertTrue(Either::left('error')->toOption()->isNone());

        self::assertSame('x', Option::some('x')->jsonSerialize());
        self::assertNull(Option::none()->jsonSerialize());

        $items = [];
        foreach (Option::some('y') as $value) {
            $items[] = $value;
        }
        self::assertSame(['y'], $items);
    }

    public function test_some_is_some_and_none_is_none(): void
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