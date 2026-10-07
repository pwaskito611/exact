<?php

declare(strict_types=1);

namespace Exact\Type\Option;

use Exact\Type\Either\Either;
use Throwable;
use Traversable;
use TypeError;

/**
 * @template T
 * @extends Option<T>
 */
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
        return self::some($fn($this->value));
    }

    public function flatMap(callable $fn): Option
    {
        $result = $fn($this->value);

        if (!$result instanceof Option) {
            throw new TypeError(
                'Some::flatMap() callback must return an Option.'
            );
        }

        return $result;
    }

    public function filter(callable $predicate): Option
    {
        return $predicate($this->value)
            ? $this
            : Option::none();
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

    public function getOrElseGet(callable $default): mixed
    {
        return $this->value;
    }

    public function getOrElseThrow(Throwable|callable $onNone): mixed
    {
        return $this->value;
    }

    public function getOrNull(): mixed
    {
        return $this->value;
    }

    public function orElse(Option $default): Option
    {
        return $this;
    }

    public function orElseGet(callable $default): Option
    {
        return $this;
    }

    public function exists(callable $predicate): bool
    {
        return $predicate($this->value);
    }

    public function forall(callable $predicate): bool
    {
        return $predicate($this->value);
    }

    public function contains(mixed $value): bool
    {
        return $this->value === $value;
    }

    public function equals(Option $other): bool
    {
        return $other instanceof self
            && $this->value === $other->value;
    }

    public function tap(callable $fn): self
    {
        $fn($this->value);

        return $this;
    }

    public function zipWith(Option $other, callable $fn): Option
    {
        if ($other->isNone()) {
            return Option::none();
        }

        return Option::some($fn($this->value, $other->value()));
    }

    public function zip(Option $other): Option
    {
        return $this->zipWith($other, fn (mixed $left, mixed $right): array => [$left, $right]);
    }

    public function ap(Option $other): Option
    {
        if (!is_callable($this->value)) {
            throw new TypeError(
                'Some::ap() requires the contained value to be callable.'
            );
        }

        if ($other->isNone()) {
            return Option::none();
        }

        return Option::some(($this->value)($other->value()));
    }

    public function flatten(): Option
    {
        if (!$this->value instanceof Option) {
            throw new TypeError(
                'Some::flatten() requires an Option value.'
            );
        }

        return $this->value;
    }

    public function toEither(mixed $left): Either
    {
        return Either::right($this->value);
    }

    public function toArray(): array
    {
        return [$this->value];
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator([$this->value]);
    }

    protected function valueOrFail(): mixed
    {
        return $this->value;
    }

    /**
     * @return T
     */
    public function value(): mixed
    {
        return $this->value;
    }
}