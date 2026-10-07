<?php

declare(strict_types=1);

namespace Exact\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use Traversable;

/**
 * Immutable double-ended queue.
 *
 * Backed by a single list. Push/pop at the back are O(1); operations at
 * the front are O(n). For heavy front operations, wrap SplDoublyLinkedList
 * (but note: that structure is mutable).
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 * @implements JsonSerializable<list<T>>
 */
final readonly class Deque implements Countable, IteratorAggregate, JsonSerializable
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
     * @param list<U> $items
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
     */
    public static function fromIterable(iterable $items): self
    {
        if (is_array($items)) {
            return new self(array_values($items));
        }

        return new self(array_values(iterator_to_array($items, false)));
    }

    // ───────────────────────── Push ─────────────────────────

    /**
     * @param T $value
     * @return self<T>
     */
    public function pushFront(mixed $value): self
    {
        return new self([$value, ...$this->items]);
    }

    /**
     * @param T $value
     * @return self<T>
     */
    public function pushBack(mixed $value): self
    {
        return new self([...$this->items, $value]);
    }

    /**
     * @param T $value
     * @return self<T>
     */
    public function prepend(mixed $value): self
    {
        return $this->pushFront($value);
    }

    /**
     * @param T $value
     * @return self<T>
     */
    public function append(mixed $value): self
    {
        return $this->pushBack($value);
    }

    // ───────────────────────── Pop ─────────────────────────

    /**
     * @return self<T>
     * @throws OutOfBoundsException
     */
    public function popFront(): self
    {
        if ($this->isEmpty()) {
            throw new OutOfBoundsException('Cannot pop from an empty Deque.');
        }

        return new self(array_slice($this->items, 1));
    }

    /**
     * @return self<T>
     * @throws OutOfBoundsException
     */
    public function popBack(): self
    {
        if ($this->isEmpty()) {
            throw new OutOfBoundsException('Cannot pop from an empty Deque.');
        }

        return new self(array_slice($this->items, 0, -1));
    }

    // ───────────────────────── Peek ─────────────────────────

    /**
     * @return T
     * @throws OutOfBoundsException
     */
    public function peekFront(): mixed
    {
        if ($this->isEmpty()) {
            throw new OutOfBoundsException('Cannot peek at an empty Deque.');
        }

        return $this->items[0];
    }

    /**
     * @return T
     * @throws OutOfBoundsException
     */
    public function peekBack(): mixed
    {
        if ($this->isEmpty()) {
            throw new OutOfBoundsException('Cannot peek at an empty Deque.');
        }

        return $this->items[count($this->items) - 1];
    }

    /**
     * @return T|null
     */
    public function peekFrontOrNull(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * @return T|null
     */
    public function peekBackOrNull(): mixed
    {
        return $this->items === []
            ? null
            : $this->items[count($this->items) - 1];
    }

    /**
     * @return T
     * @throws OutOfBoundsException
     */
    public function first(): mixed
    {
        return $this->peekFront();
    }

    /**
     * @return T
     * @throws OutOfBoundsException
     */
    public function last(): mixed
    {
        return $this->peekBack();
    }

    // ───────────────────────── Query ─────────────────────────

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function size(): int
    {
        return count($this->items);
    }

    /**
     * @return T
     * @throws OutOfBoundsException
     */
    public function at(int $index): mixed
    {
        if ($index < 0 || $index >= count($this->items)) {
            throw new OutOfBoundsException(
                "Index {$index} out of range."
            );
        }

        return $this->items[$index];
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        return new self(array_map($fn, $this->items));
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        return new self(
            array_values(array_filter($this->items, $predicate))
        );
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

    /**
     * @param self<T> $other
     */
    public function equals(self $other): bool
    {
        return $this->items === $other->items;
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