<?php

declare(strict_types=1);

namespace Exact\Exception;

final class MatchException extends \RuntimeException
{
    public static function noMatch(mixed $value): self
    {
        return new self(
            sprintf(
                'No matching case found for value of type %s.',
                get_debug_type($value),
            ),
        );
    }

    public static function notExhaustive(mixed $value): self
    {
        return new self(
            sprintf(
                'Non-exhaustive match: no case matched value of type %s.',
                get_debug_type($value),
            ),
        );
    }
}