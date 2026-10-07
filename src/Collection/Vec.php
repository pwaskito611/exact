<?php

declare(strict_types=1);

namespace Exact\Collection;

use ArrayIterator;
use Countable;
use Exact\Exception\EmptyCollectionException;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use Traversable;
use TypeError;

/**
 * Immutable, eager, index-addressable homogeneous sequence.
 *
 * Fixed-length operations return a new Vec; every combinator is eager.
 * For lazy pipelines use LazySeq.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 * @implements JsonSerializable<list<T>>
 */
final readonly class Vec implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param list<T> $items
     */
    private function __construct(
        private array $items,
    ) {
    }

    // ───────────────────────── Constructors ─────────────────────────

    /** @return self<never> */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @template U
     * @param U ...$items
     * @return self<U>
     */
    public static function of(mixed ...$items): self
    {
        return new self(array_values($items));
    }

    /**
     * @template U
     * @param U $value
     * @return self<U>
     */
    public static function singleton(mixed $value): self
    {
        return new self([$value]);
    }

    /**
     * @template U
     * @param iterable<U> $items
     * @return self<U>
     */
    public static function fromIterable(iterable $items): self
    {
        if (is_array($items)) {
            return new self(array_values($items));
        }

        return new self(array_values(iterator_to_array($items, false)));
    }

    /**
     * @template U
     * @param list<U> $items
     * @return self<U>
     */
    public static function fromArray(array $items): self
    {
        return new self(array_values($items));
    }

    // ───────────────────────── Query ─────────────────────────

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return $this->items !== [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function size(): int
    {
        return $this->count();
    }

    // ───────────────────────── Access ─────────────────────────

    /**
     * @return T
     * @throws OutOfBoundsException
     */
    public function at(int $index): mixed
    {
        if ($index < 0 || $index >= count($this->items)) {
            throw new OutOfBoundsException(
                "Index {$index} out of range [0, " . count($this->items) . ")."
            );
        }

        return $this->items[$index];
    }

    /**
     * @return T|null
     */
    public function atOrNull(int $index): mixed
    {
        return $this->items[$index] ?? null;
    }

    /**
     * @return T
     * @throws OutOfBoundsException
     */
    public function first(): mixed
    {
        if ($this->isEmpty()) {
            throw new EmptyCollectionException(
                'Cannot get first element of an empty Vec.'
            );
        }

        return $this->items[0];
    }

    /**
     * @return T
     * @throws OutOfBoundsException
     */
    public function last(): mixed
    {
        if ($this->isEmpty()) {
            throw new EmptyCollectionException(
                'Cannot get last element of an empty Vec.'
            );
        }

        return $this->items[$this->count() - 1];
    }

    /**
     * @return T|null
     */
    public function firstOrNull(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * @return T|null
     */
    public function lastOrNull(): mixed
    {
        return $this->items === []
            ? null
            : $this->items[$this->count() - 1];
    }

    /**
     * Haskell-style alias for first().
     *
     * @return T
     * @throws OutOfBoundsException
     */
    public function head(): mixed
    {
        return $this->first();
    }

    /**
     * Haskell-style tail: everything except the first element.
     *
     * @return self<T>
     * @throws OutOfBoundsException
     */
    public function tail(): self
    {
        if ($this->isEmpty()) {
            throw new EmptyCollectionException(
                'Cannot get tail of an empty Vec.'
            );
        }

        return new self(array_slice($this->items, 1));
    }

    /**
     * Alias for at().
     *
     * @return T
     * @throws OutOfBoundsException
     */
    public function get(int $index): mixed
    {
        return $this->at($index);
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        $result = [];

        foreach ($this->items as $item) {
            $result[] = $fn($item);
        }

        return new self($result);
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        $result = [];

        foreach ($this->items as $item) {
            if ($predicate($item)) {
                $result[] = $item;
            }
        }

        return new self($result);
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function reject(callable $predicate): self
    {
        return $this->filter(
            static fn (mixed $value): bool => !$predicate($value)
        );
    }

    /**
     * @template U
     * @param callable(T): self<U> $fn
     * @return self<U>
     */
    public function flatMap(callable $fn): self
    {
        $result = [];

        foreach ($this->items as $item) {
            $mapped = $fn($item);

            if (!$mapped instanceof self) {
                throw new TypeError(
                    'Vec::flatMap() callback must return a Vec.'
                );
            }

            foreach ($mapped->items as $inner) {
                $result[] = $inner;
            }
        }

        return new self($result);
    }

    /**
     * @template U
     * @param U $initial
     * @param callable(U, T): U $fn
     * @return U
     */
    public function foldLeft(mixed $initial, callable $fn): mixed
    {
        $accumulator = $initial;

        foreach ($this->items as $item) {
            $accumulator = $fn($accumulator, $item);
        }

        return $accumulator;
    }

    /**
     * @template U
     * @param U $initial
     * @param callable(T, U): U $fn
     * @return U
     */
    public function foldRight(mixed $initial, callable $fn): mixed
    {
        $accumulator = $initial;

        foreach (array_reverse($this->items) as $item) {
            $accumulator = $fn($item, $accumulator);
        }

        return $accumulator;
    }

    /**
     * @param callable(T, T): T $fn
     * @return T
     * @throws OutOfBoundsException
     */
    public function reduce(callable $fn): mixed
    {
        if ($this->isEmpty()) {
            throw new OutOfBoundsException('Cannot reduce an empty Vec.');
        }

        $items = $this->items;
        $accumulator = array_shift($items);

        foreach ($items as $item) {
            $accumulator = $fn($accumulator, $item);
        }

        return $accumulator;
    }

    // ───────────────────────── Predicates ─────────────────────────

    /**
     * @param T $value
     */
    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function exists(callable $predicate): bool
    {
        foreach ($this->items as $item) {
            if ($predicate($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function forall(callable $predicate): bool
    {
        foreach ($this->items as $item) {
            if (!$predicate($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param self<T> $other
     */
    public function equals(self $other): bool
    {
        return $this->items === $other->items;
    }

    // ───────────────────────── Combinators ─────────────────────────

    /**
     * @param T $value
     * @return self<T>
     */
    public function append(mixed $value): self
    {
        return new self([...$this->items, $value]);
    }

    /**
     * @param T $value
     * @return self<T>
     */
    public function prepend(mixed $value): self
    {
        return new self([$value, ...$this->items]);
    }

    /**
     * @param self<T> $other
     * @return self<T>
     */
    public function concat(self $other): self
    {
        return new self([...$this->items, ...$other->items]);
    }

    /**
     * @return self<T>
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->items));
    }

    /**
     * @return self<T>
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new self(array_slice($this->items, $offset, $length));
    }

    /**
     * @return self<T>
     */
    public function take(int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Count cannot be negative.');
        }

        return new self(array_slice($this->items, 0, $count));
    }

    /**
     * @return self<T>
     */
    public function drop(int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Count cannot be negative.');
        }

        return new self(array_slice($this->items, $count));
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function takeWhile(callable $predicate): self
    {
        $result = [];

        foreach ($this->items as $item) {
            if (!$predicate($item)) {
                break;
            }
            $result[] = $item;
        }

        return new self($result);
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function dropWhile(callable $predicate): self
    {
        $result = [];
        $dropping = true;

        foreach ($this->items as $item) {
            if ($dropping && $predicate($item)) {
                continue;
            }
            $dropping = false;
            $result[] = $item;
        }

        return new self($result);
    }

    /**
     * @return self<T>
     */
    public function unique(): self
    {
        $seen = [];
        $result = [];

        foreach ($this->items as $item) {
            $key = is_scalar($item) || $item === null
                ? gettype($item) . ':' . (string) $item
                : serialize($item);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $item;
        }

        return new self($result);
    }

    /**
     * @template U
     * @param self<U> $other
     * @return self<array{0: T, 1: U}>
     */
    public function zip(self $other): self
    {
        return $this->zipWith(
            $other,
            fn (mixed $a, mixed $b): array => [$a, $b],
        );
    }

    /**
     * @template U
     * @template V
     * @param self<U> $other
     * @param callable(T, U): V $fn
     * @return self<V>
     */
    public function zipWith(self $other, callable $fn): self
    {
        $result = [];
        $a = $this->items;
        $b = $other->items;
        $size = min(count($a), count($b));

        for ($i = 0; $i < $size; $i++) {
            $result[] = $fn($a[$i], $b[$i]);
        }

        return new self($result);
    }

    /**
     * @return self<array{0: int, 1: T}>
     */
    public function enumerate(): self
    {
        $result = [];

        foreach ($this->items as $index => $item) {
            $result[] = [$index, $item];
        }

        return new self($result);
    }

    /**
     * @param callable(T): mixed $fn
     * @return self<T>
     */
    public function tap(callable $fn): self
    {
        foreach ($this->items as $item) {
            $fn($item);
        }

        return $this;
    }

    /**
     * Alias for tap(); returns self for chaining.
     *
     * @param callable(T): mixed $fn
     * @return self<T>
     */
    public function each(callable $fn): self
    {
        return $this->tap($fn);
    }

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return list<T>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return list<T>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}

if (!class_exists('Exact\\Collection\\Vec', false)) {
    class_alias(Vec::class, 'Exact\\Collection\\Vec');
}