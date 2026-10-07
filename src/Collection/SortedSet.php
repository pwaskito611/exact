<?php

declare(strict_types=1);

namespace Exact\Collection;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Immutable sorted set with a user-supplied comparator.
 *
 * Elements are stored in ascending order per the comparator. Duplicate
 * elements (per the comparator) are rejected. Uses O(n) insertion and
 * O(n) lookup; suitable for small-to-medium sets. For large sets consider
 * a hash-based Set plus on-demand sorting.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 * @implements JsonSerializable<list<T>>
 */
final readonly class SortedSet implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var callable(T, T): int */
    private Closure $comparator;

    /**
     * @param list<T> $items
     * @param callable(T, T): int $comparator
     */
    private function __construct(
        private array $items,
        callable $comparator,
    ) {
        $this->comparator = Closure::fromCallable($comparator);
    }

    // ───────────────────────── Constructors ─────────────────────────

    /**
     * @return self<never>
     */
    public static function empty(?callable $comparator = null): self
    {
        return new self([], $comparator ?? self::defaultComparator());
    }

    /**
     * @template U
     * @param callable(U, U): int $comparator
     * @param U ...$items
     * @return self<U>
     */
    public static function of(callable $comparator, mixed ...$items): self
    {
        $instance = new self([], $comparator);
        $result = $instance;

        foreach ($items as $item) {
            $result = $result->add($item);
        }

        return $result;
    }

    /**
     * @template U
     * @param iterable<U> $items
     * @param callable(U, U): int $comparator
     * @return self<U>
     */
    public static function fromIterable(iterable $items, callable $comparator): self
    {
        $instance = new self([], $comparator);
        $result = $instance;

        foreach ($items as $item) {
            $result = $result->add($item);
        }

        return $result;
    }

    /**
     * @template U
     * @param list<U> $items
     * @param callable(U, U): int $comparator
     * @return self<U>
     */
    public static function fromArray(array $items, callable $comparator): self
    {
        return self::fromIterable($items, $comparator);
    }

    // ───────────────────────── Operations ─────────────────────────

    /**
     * @param T $value
     * @return self<T>
     */
    public function add(mixed $value): self
    {
        $index = $this->binarySearch($value);

        if ($index >= 0) {
            return $this;
        }

        $items = $this->items;
        array_splice($items, -$index - 1, 0, [$value]);

        return new self($items, $this->comparator);
    }

    /**
     * @param T $value
     * @return self<T>
     */
    public function remove(mixed $value): self
    {
        $index = $this->binarySearch($value);

        if ($index < 0) {
            return $this;
        }

        $items = $this->items;
        array_splice($items, $index, 1);

        return new self($items, $this->comparator);
    }

    /**
     * @param T $value
     */
    public function contains(mixed $value): bool
    {
        return $this->binarySearch($value) >= 0;
    }

    /**
     * @return T|null
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * @return T|null
     */
    public function last(): mixed
    {
        return $this->items === []
            ? null
            : $this->items[count($this->items) - 1];
    }

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

    // ───────────────────────── Set algebra ─────────────────────────

    /**
     * @param self<T> $other
     * @return self<T>
     */
    public function union(self $other): self
    {
        return $this->merge($other->items);
    }

    /**
     * @param self<T> $other
     * @return self<T>
     */
    public function intersect(self $other): self
    {
        $result = $this->empty($this->comparator);

        foreach ($this->items as $item) {
            if ($other->contains($item)) {
                $result = $result->add($item);
            }
        }

        return $result;
    }

    /**
     * @param self<T> $other
     * @return self<T>
     */
    public function difference(self $other): self
    {
        $result = $this->empty($this->comparator);

        foreach ($this->items as $item) {
            if (!$other->contains($item)) {
                $result = $result->add($item);
            }
        }

        return $result;
    }

    /**
     * @param self<T> $other
     * @return self<T>
     */
    public function symmetricDifference(self $other): self
    {
        return $this->difference($other)->union($other->difference($this));
    }

    /**
     * @param self<T> $other
     */
    public function isSubsetOf(self $other): bool
    {
        foreach ($this->items as $item) {
            if (!$other->contains($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param self<T> $other
     */
    public function isSupersetOf(self $other): bool
    {
        return $other->isSubsetOf($this);
    }

    /**
     * @param T $low
     * @param T $high
     * @return self<T>
     */
    public function range(mixed $low, mixed $high): self
    {
        $cmp = $this->comparator;
        $result = $this->empty($this->comparator);

        foreach ($this->items as $item) {
            if ($cmp($item, $low) >= 0 && $cmp($item, $high) <= 0) {
                $result = $result->add($item);
            }
        }

        return $result;
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * @template U
     * @param callable(T): U $fn
     * @param callable(U, U): int $comparator
     * @return self<U>
     */
    public function map(callable $fn, callable $comparator): self
    {
        $result = new self([], $comparator);

        foreach ($this->items as $item) {
            $result = $result->add($fn($item));
        }

        return $result;
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        $result = $this->empty($this->comparator);

        foreach ($this->items as $item) {
            if ($predicate($item)) {
                $result = $result->add($item);
            }
        }

        return $result;
    }

    /**
     * @template U
     * @param U $initial
     * @param callable(U, T): U $fn
     * @return U
     */
    public function fold(mixed $initial, callable $fn): mixed
    {
        $accumulator = $initial;

        foreach ($this->items as $item) {
            $accumulator = $fn($accumulator, $item);
        }

        return $accumulator;
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
        if ($this->count() !== $other->count()) {
            return false;
        }

        foreach ($this->items as $index => $item) {
            if (!$other->contains($item)) {
                return false;
            }
        }

        return true;
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

    // ───────────────────────── Internals ─────────────────────────

    /**
     * @param T $value
     * @return int  index if found; -(insertion point) - 1 otherwise
     */
    private function binarySearch(mixed $value): int
    {
        $cmp = $this->comparator;
        $low = 0;
        $high = count($this->items) - 1;

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $result = $cmp($this->items[$mid], $value);

            if ($result === 0) {
                return $mid;
            }

            if ($result < 0) {
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        return -$low - 1;
    }

    /**
     * @param list<T> $items
     * @return self<T>
     */
    private function merge(array $items): self
    {
        $result = $this;

        foreach ($items as $item) {
            $result = $result->add($item);
        }

        return $result;
    }

    /**
     * @return callable(mixed, mixed): int
     */
    private static function defaultComparator(): callable
    {
        return static fn (mixed $a, mixed $b): int => $a <=> $b;
    }
}