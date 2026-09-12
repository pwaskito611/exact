<?php

declare(strict_types=1);

namespace Exact\Data\Option;

abstract readonly class Option
{
    public static function some(mixed $value): self
    {
        return new Some($value);
    }

    public static function none(): self
    {
        return new None();
    }

    public static function from(mixed $value): self
    {
        return $value === null
            ? new None()
            : new Some($value);
    }

    abstract public function isSome(): bool;

    abstract public function isNone(): bool;

    abstract public function map(callable $fn): self;

    abstract public function flatMap(callable $fn): self;

    abstract public function filter(callable $predicate): self;

    abstract public function fold(
        callable $onNone,
        callable $onSome,
    ): mixed;

    abstract public function getOrElse(mixed $default): mixed;

    abstract public function orElse(self $default): self;
}