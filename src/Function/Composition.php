<?php

declare(strict_types=1);

namespace Exact\Function;

final class Composition
{
    public static function compose(
        callable ...$functions,
    ): callable {
        return static function (mixed $value) use ($functions): mixed {
            foreach (array_reverse($functions) as $function) {
                $value = $function($value);
            }

            return $value;
        };
    }

    public static function pipe(
        callable ...$functions,
    ): callable {
        return static function (mixed $value) use ($functions): mixed {
            foreach ($functions as $function) {
                $value = $function($value);
            }

            return $value;
        };
    }
}