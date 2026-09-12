<?php

declare(strict_types=1);

namespace Exact\Data\Option;

final readonly class Some extends Option
{
    public function __construct(
        private mixed $value,
    ) {
    }

    public function isSome(): bool
    {
        return true;
    }

    public function isNone(): bool
    {
        return false;
    }

    public function map(callable $fn): Option
    {
        return new Some($fn($this->value));
    }

    public function flatMap(callable $fn): Option
    {
        return $fn($this->value);
    }

    public function filter(callable $predicate): Option
    {
        return $predicate($this->value)
            ? $this
            : new None();
    }

    public function fold(
        callable $onNone,
        callable $onSome,
    ): mixed {
        return $onSome($this->value);
    }

    public function getOrElse(mixed $default): mixed
    {
        return $this->value;
    }

    public function orElse(Option $default): Option
    {
        return $this;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}