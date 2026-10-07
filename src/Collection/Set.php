<?php

declare(strict_types=1);

namespace Exact\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Immutable set of unique values.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 * @implements JsonSerializable<list<T>>
 */
final readonly class Set implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param array<int, T> $items
     */
    private function __construct(
        private array $items,
    ) {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param T ...$items
     */
    public static function of(mixed ...$items): self
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
     * @param iterable<T> $items
     */
    public static function fromIterable(iterable $items): self
    {
        return self::of(...$items);
    }

    /**
     * @param list<T> $items
     */
    public static function fromArray(array $items): self
    {
        return self::of(...$items);
    }

    /**
     * @param T $value
     */
    public function add(mixed $value): self
    {
        if ($this->contains($value)) {
            return $this;
        }

        return new self([
            ...$this->items,
            $value,
        ]);
    }

    /**
     * @param T $value
     */
    public function remove(mixed $value): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->items,
                    fn (mixed $item): bool => $item !== $value,
                )
            )
        );
    }

    /**
     * @param T $value
     */
    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function size(): int
    {
        return count($this->items);
    }

    public function count(): int
    {
        return $this->size();
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @param self<T> $other
     */
    public function union(self $other): self
    {
        return self::of(
            ...$this->items,
            ...$other->items,
        );
    }

    /**
     * @param self<T> $other
     */
    public function intersect(self $other): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->items,
                    fn (mixed $item): bool => $other->contains($item),
                )
            )
        );
    }

    /**
     * @param self<T> $other
     */
    public function difference(self $other): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->items,
                    fn (mixed $item): bool => !$other->contains($item),
                )
            )
        );
    }

    /**
     * @param self<T> $other
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
     * @param self<T> $other
     */
    public function isDisjoint(self $other): bool
    {
        foreach ($this->items as $item) {
            if ($other->contains($item)) {
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
        return $this->size() === $other->size()
            && $this->isSubsetOf($other);
    }

    /**
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        return self::of(
            ...array_map($fn, $this->items),
        );
    }

    /**
     * @template U
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        return new self(
            array_values(
                array_filter($this->items, $predicate)
            )
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
        $result = $initial;

        foreach ($this->items as $item) {
            $result = $fn($result, $item);
        }

        return $result;
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
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, T>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @return list<T>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}