<?php

declare(strict_types=1);

namespace Exact\Type\Option;

use Exact\Type\Either\Either;
use Throwable;
use TypeError;

/**
 * @template T
 * @implements \IteratorAggregate<int, T>
 */
abstract readonly class Option implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @template U
     * @param U $value
     * @return self<U>
     */
    public static function some(mixed $value): self
    {
        return new Some($value);
    }

    /**
     * @return self<never>
     */
    public static function none(): self
    {
        return new None();
    }

    /**
     * @template U
     * @param U|null $value
     * @return self<U>
     */
    public static function from(mixed $value): self
    {
        return $value === null
            ? self::none()
            : self::some($value);
    }

    /**
     * @template U
     * @param U $value
     * @return self<U>
     */
    public static function when(bool $condition, mixed $value): self
    {
        return $condition
            ? self::some($value)
            : self::none();
    }

    /**
     * @template U
     * @param callable(): U $fn
     * @return self<U>
     */
    public static function tryCatch(callable $fn): self
    {
        try {
            return self::some($fn());
        } catch (Throwable) {
            return self::none();
        }
    }

    /**
     * @template U
     * @param iterable<self<U>> $options
     * @return self<U>
     */
    public static function firstSome(iterable $options): self
    {
        foreach ($options as $option) {
            if (!$option instanceof self) {
                throw new TypeError(
                    'Option::firstSome() requires every element to be an Option.'
                );
            }

            if ($option->isSome()) {
                return $option;
            }
        }

        return self::none();
    }

    /**
     * @template U
     * @param iterable<self<U>> $options
     * @return self<list<U>>
     */
    public static function sequence(iterable $options): self
    {
        $values = [];

        foreach ($options as $option) {
            if (!$option instanceof self) {
                throw new TypeError(
                    'Option::sequence() requires every item to be an Option.'
                );
            }

            if ($option->isNone()) {
                return self::none();
            }

            $values[] = $option->valueOrFail();
        }

        return self::some($values);
    }

    /**
     * @template U
     * @template V
     * @param iterable<U> $items
     * @param callable(U): self<V> $fn
     * @return self<list<V>>
     */
    public static function traverse(iterable $items, callable $fn): self
    {
        $values = [];

        foreach ($items as $item) {
            $result = $fn($item);

            if (!$result instanceof self) {
                throw new TypeError(
                    'Option::traverse() callback must return an Option.'
                );
            }

            if ($result->isNone()) {
                return self::none();
            }

            $values[] = $result->valueOrFail();
        }

        return self::some($values);
    }

    /**
     * @template U
     * @param iterable<self<U>> $options
     * @return list<U>
     */
    public static function collect(iterable $options): array
    {
        $values = [];

        foreach ($options as $option) {
            if (!$option instanceof self) {
                throw new TypeError(
                    'Option::collect() requires every item to be an Option.'
                );
            }

            if ($option->isSome()) {
                $values[] = $option->valueOrFail();
            }
        }

        return $values;
    }

    abstract public function isSome(): bool;

    abstract public function isNone(): bool;

    /**
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    abstract public function map(callable $fn): self;

    /**
     * @template U
     * @param callable(T): self<U> $fn
     * @return self<U>
     */
    abstract public function flatMap(callable $fn): self;

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    abstract public function filter(callable $predicate): self;

    abstract public function fold(
        callable $onNone,
        callable $onSome,
    ): mixed;

    abstract public function getOrElse(mixed $default): mixed;

    abstract public function getOrElseGet(callable $default): mixed;

    /**
     * @param Throwable|callable(): Throwable $onNone
     * @return T
     */
    abstract public function getOrElseThrow(Throwable|callable $onNone): mixed;

    /**
     * @return T|null
     */
    abstract public function getOrNull(): mixed;

    /**
     * @param self<T> $default
     * @return self<T>
     */
    abstract public function orElse(self $default): self;

    /**
     * @param callable(): self<T> $default
     * @return self<T>
     */
    abstract public function orElseGet(callable $default): self;

    /**
     * @param callable(T): bool $predicate
     */
    abstract public function exists(callable $predicate): bool;

    /**
     * @param callable(T): bool $predicate
     */
    abstract public function forall(callable $predicate): bool;

    abstract public function contains(mixed $value): bool;

    abstract public function equals(self $other): bool;

    /**
     * @param callable(T): mixed $fn
     * @return self<T>
     */
    abstract public function tap(callable $fn): self;

    /**
     * @template U
     * @template V
     * @param self<U> $other
     * @param callable(T, U): V $fn
     * @return self<V>
     */
    abstract public function zipWith(self $other, callable $fn): self;

    /**
     * @template U
     * @param self<U> $other
     * @return self<array{0: T, 1: U}>
     */
    abstract public function zip(self $other): self;

    /**
     * @template U
     * @param self<callable(T): U> $other
     * @return self<U>
     */
    abstract public function ap(self $other): self;

    /**
     * @return Option<T>
     */
    abstract public function flatten(): Option;

    /**
     * @template L
     * @param L $left
     * @return Either<L, T>
     */
    abstract public function toEither(mixed $left): Either;

    /**
     * @return list<T>
     */
    abstract public function toArray(): array;

    /**
     * @return \Traversable<int, T>
     */
    abstract public function getIterator(): \Traversable;

    /**
     * @return mixed
     */
    abstract public function jsonSerialize(): mixed;

    /**
     * @return T
     */
    abstract protected function valueOrFail(): mixed;
}