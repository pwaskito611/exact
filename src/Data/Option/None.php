<?php

declare(strict_types=1);

namespace Exact\Data\Option;

final readonly class None extends Option
{
    public function isSome(): bool
    {
        return false;
    }

    public function isNone(): bool
    {
        return true;
    }

    public function map(callable $fn): Option
    {
        return $this;
    }

    public function flatMap(callable $fn): Option
    {
        return $this;
    }

    public function filter(callable $predicate): Option
    {
        return $this;
    }

    public function fold(
        callable $onNone,
        callable $onSome,
    ): mixed {
        return $onNone();
    }

    public function getOrElse(mixed $default): mixed
    {
        return $default;
    }

    public function orElse(Option $default): Option
    {
        return $default;
    }
}