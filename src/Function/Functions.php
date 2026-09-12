<?php

declare(strict_types=1);

namespace Exact\Function;

final class Functions
{
    public static function identity(): callable
    {
        return static fn (mixed $value): mixed => $value;
    }

    public static function constant(mixed $value): callable
    {
        return static fn (...$args): mixed => $value;
    }

    public static function curry(
        callable $fn,
        int $arity,
    ): callable {
        if ($arity < 1) {
            throw new \InvalidArgumentException(
                'Arity must be greater than zero.'
            );
        }

        $curry = function (array $args) use (
            $fn,
            $arity,
            &$curry,
        ): mixed {
            if (count($args) >= $arity) {
                return $fn(...$args);
            }

            return static function (mixed $value) use (
                $args,
                &$curry,
            ): mixed {
                return $curry([
                    ...$args,
                    $value,
                ]);
            };
        };

        return static fn (mixed $value): mixed => $curry([$value]);
    }

    public static function partial(
        callable $fn,
        mixed ...$args,
    ): callable {
        return static fn (...$remaining) => $fn(
            ...$args,
            ...$remaining,
        );
    }
}