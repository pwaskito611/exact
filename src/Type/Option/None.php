<?php

declare(strict_types=1);

namespace Exact\Type\Option;

use Exact\Type\Either\Either;
use EmptyIterator;
use Throwable;
use Traversable;
use TypeError;

/**
 * @extends Option<never>
 */
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

    public function getOrElseGet(callable $default): mixed
    {
        return $default();
    }

    public function getOrElseThrow(Throwable|callable $onNone): mixed
    {
        if ($onNone instanceof Throwable) {
            throw $onNone;
        }

        $exception = $onNone();

        if (!$exception instanceof Throwable) {
            throw new TypeError(
                'None::getOrElseThrow() callable must return a Throwable.'
            );
        }

        throw $exception;
    }

    public function getOrNull(): mixed
    {
        return null;
    }

    public function orElse(Option $default): Option
    {
        return $default;
    }

    public function orElseGet(callable $default): Option
    {
        $result = $default();

        if (!$result instanceof Option) {
            throw new TypeError(
                'None::orElseGet() callback must return an Option.'
            );
        }

        return $result;
    }

    public function exists(callable $predicate): bool
    {
        return false;
    }

    public function forall(callable $predicate): bool
    {
        return true;
    }

    public function contains(mixed $value): bool
    {
        return false;
    }

    public function equals(Option $other): bool
    {
        return $other instanceof None;
    }

    public function tap(callable $fn): self
    {
        return $this;
    }

    public function zipWith(Option $other, callable $fn): Option
    {
        return Option::none();
    }

    public function zip(Option $other): Option
    {
        return Option::none();
    }

    public function ap(Option $other): Option
    {
        return Option::none();
    }

    public function flatten(): Option
    {
        return $this;
    }

    public function toEither(mixed $left): Either
    {
        return Either::left($left);
    }

    public function toArray(): array
    {
        return [];
    }

    public function jsonSerialize(): mixed
    {
        return null;
    }

    public function getIterator(): Traversable
    {
        return new EmptyIterator();
    }

    protected function valueOrFail(): mixed
    {
        throw new \LogicException('Cannot access value on None.');
    }

    /**
     * @return never
     */
    public function value(): mixed
    {
        throw new \LogicException('Cannot access value on None.');
    }
}