<?php

declare(strict_types=1);

namespace Exact\Collection;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use Traversable;

/**
 * Immutable non-empty sequence.
 *
 * Guaranteed to contain at least one element. Operations that may yield
 * an empty result (filter, reject, tail, init) return Vec instead.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 * @implements JsonSerializable<non-empty-list<T>>
 */
final readonly class NonEmptyVec implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param non-empty-list<T> $items
     */
    private function __construct(
        private array $items,
    ) {
        if ($items === []) {
            throw new InvalidArgumentException(
                'NonEmptyVec cannot be empty.'
            );
        }
    }

    // ───────────────────────── Constructors ─────────────────────────

    /**
     * @template U
     * @param U $head
     * @param U ...$tail
     * @return self<U>
     */
    public static function of(mixed $head, mixed ...$tail): self
    {
        return new self([$head, ...$tail]);
    }

    /**
     * @template U
     * @param non-empty-list<U> $items
     * @return self<U>
     */
    public static function fromArray(array $items): self
    {
        return new self(array_values($items));
    }

    /**
     * @template U
     * @param iterable<U> $items
     * @return self<U>
     * @throws InvalidArgumentException if the iterable is empty
     */
    public static function fromIterable(iterable $items): self
    {
        $array = is_array($items)
            ? array_values($items)
            : array_values(iterator_to_array($items, false));

        return new self($array);
    }

    /**
     * @template U
     * @param Vec<U> $vec
     * @return self<U>|null
     */
    public static function fromSeq(Vec $vec): ?self
    {
        if ($vec->isEmpty()) {
            return null;
        }

        return new self($vec->toArray());
    }

    // ───────────────────────── Query ─────────────────────────

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
     */
    public function head(): mixed
    {
        return $this->items[0];
    }

    /**
     * @return T
     */
    public function last(): mixed
    {
        return $this->items[count($this->items) - 1];
    }

    /**
     * Everything except the first element. May be empty.
     *
     * @return Vec<T>
     */
    public function tail(): Vec
    {
        return Vec::fromArray(array_slice($this->items, 1));
    }

    /**
     * Everything except the last element. May be empty.
     *
     * @return Vec<T>
     */
    public function init(): Vec
    {
        return Vec::fromArray(array_slice($this->items, 0, -1));
    }

    /**
     * @return T
     * @throws OutOfBoundsException
     */
    public function get(int $index): mixed
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
    public function getOrNull(int $index): mixed
    {
        return $this->items[$index] ?? null;
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
     * May yield an empty result; returns Vec.
     *
     * @param callable(T): bool $predicate
     * @return Vec<T>
     */
    public function filter(callable $predicate): Vec
    {
        $result = [];

        foreach ($this->items as $item) {
            if ($predicate($item)) {
                $result[] = $item;
            }
        }

        return Vec::fromArray($result);
    }

    /**
     * @param callable(T): bool $predicate
     * @return Vec<T>
     */
    public function reject(callable $predicate): Vec
    {
        return $this->filter(
            static fn (mixed $value): bool => !$predicate($value)
        );
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
     */
    public function reduce(callable $fn): mixed
    {
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

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return non-empty-list<T>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @return Vec<T>
     */
    public function toVec(): Vec
    {
        return Vec::fromArray($this->items);
    }

    /**
     * @return Vec<T>
     */
    public function toSeq(): Vec
    {
        return $this->toVec();
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return non-empty-list<T>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}

if (!class_exists('Exact\\Collection\\NonEmptyVec', false)) {
    class_alias(NonEmptyVec::class, 'Exact\\Collection\\NonEmptyVec');
}