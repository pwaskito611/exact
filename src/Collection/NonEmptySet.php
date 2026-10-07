<?php

declare(strict_types=1);

namespace Exact\Collection;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Immutable set guaranteed to contain at least one element.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 * @implements JsonSerializable<non-empty-list<T>>
 */
final readonly class NonEmptySet implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param non-empty-list<T> $items
     */
    private function __construct(
        private array $items,
    ) {
        if ($items === []) {
            throw new InvalidArgumentException(
                'NonEmptySet cannot be empty.'
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
        $items = [$head];

        foreach ($tail as $item) {
            if (!in_array($item, $items, true)) {
                $items[] = $item;
            }
        }

        return new self($items);
    }

    /**
     * @template U
     * @param non-empty-list<U> $items
     * @return self<U>
     */
    public static function fromArray(array $items): self
    {
        $result = [];

        foreach ($items as $item) {
            if (!in_array($item, $result, true)) {
                $result[] = $item;
            }
        }

        return new self($result);
    }

    /**
     * @template U
     * @param Set<U> $set
     * @return self<U>
     * @throws InvalidArgumentException
     */
    public static function fromSet(Set $set): self
    {
        return self::fromArray($set->toArray());
    }

    // ───────────────────────── Access ─────────────────────────

    /**
     * @return T
     */
    public function head(): mixed
    {
        return $this->items[0];
    }

    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function size(): int
    {
        return count($this->items);
    }

    // ───────────────────────── Operations ─────────────────────────

    /**
     * @param T $value
     * @return self<T>
     */
    public function add(mixed $value): self
    {
        if ($this->contains($value)) {
            return $this;
        }

        return new self([...$this->items, $value]);
    }

    /**
     * May become empty → returns Set.
     *
     * @param T $value
     * @return Set<T>
     */
    public function remove(mixed $value): Set
    {
        return Set::fromArray(
            array_values(array_filter(
                $this->items,
                static fn (mixed $item): bool => $item !== $value,
            ))
        );
    }

    /**
     * @param self<T> $other
     * @return self<T>
     */
    public function union(self $other): self
    {
        $result = $this;

        foreach ($other->items as $item) {
            $result = $result->add($item);
        }

        return $result;
    }

    /**
     * May become empty → returns Set.
     *
     * @param self<T> $other
     * @return Set<T>
     */
    public function intersect(self $other): Set
    {
        return Set::fromArray(
            array_values(array_filter(
                $this->items,
                static fn (mixed $item): bool => $other->contains($item),
            ))
        );
    }

    /**
     * @param self<T> $other
     */
    public function equals(self $other): bool
    {
        return $this->size() === $other->size()
            && $this->isSubsetOf($other);
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

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        return self::fromArray(array_map($fn, $this->items));
    }

    /**
     * May become empty → returns Set.
     *
     * @param callable(T): bool $predicate
     * @return Set<T>
     */
    public function filter(callable $predicate): Set
    {
        return Set::fromArray(
            array_values(array_filter($this->items, $predicate))
        );
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
     * @return Set<T>
     */
    public function toSet(): Set
    {
        return Set::fromArray($this->items);
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