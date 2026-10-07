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
 * Immutable map guaranteed to contain at least one entry.
 *
 * Operations that may yield an empty result (remove, filter) return Map.
 *
 * @template TValue
 * @implements IteratorAggregate<array-key, TValue>
 * @implements JsonSerializable<non-empty-array<array-key, TValue>>
 */
final readonly class NonEmptyMap implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param non-empty-array<array-key, TValue> $items
     */
    private function __construct(
        private array $items,
    ) {
        if ($items === []) {
            throw new InvalidArgumentException(
                'NonEmptyMap cannot be empty.'
            );
        }
    }

    // ───────────────────────── Constructors ─────────────────────────

    /**
     * @template V
     * @param V $firstValue
     * @return self<V>
     */
    public static function singleton(int|string $key, mixed $firstValue): self
    {
        return new self([$key => $firstValue]);
    }

    /**
     * @template V
     * @param non-empty-array<array-key, V> $items
     * @return self<V>
     */
    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    /**
     * @template V
     * @param Map<V> $map
     * @return self<V>
     * @throws InvalidArgumentException if $map is empty
     */
    public static function fromMap(Map $map): self
    {
        return new self($map->toArray());
    }

    /**
     * @template V
     * @param iterable<array-key, V> $items
     * @return self<V>
     */
    public static function fromIterable(iterable $items): self
    {
        if (is_array($items)) {
            return new self($items);
        }

        return new self(iterator_to_array($items));
    }

    // ───────────────────────── Access ─────────────────────────

    /**
     * First entry as [key, value].
     *
     * @return array{0: array-key, 1: TValue}
     */
    public function head(): array
    {
        $key = array_key_first($this->items);

        return [$key, $this->items[$key]];
    }

    /**
     * @return TValue
     * @throws OutOfBoundsException
     */
    public function get(int|string $key): mixed
    {
        if (!array_key_exists($key, $this->items)) {
            throw new OutOfBoundsException(
                'Key not found: ' . var_export($key, true)
            );
        }

        return $this->items[$key];
    }

    /**
     * @return TValue|null
     */
    public function getOrNull(int|string $key): mixed
    {
        return $this->items[$key] ?? null;
    }

    public function has(int|string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * @return list<array-key>
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    /**
     * @return list<TValue>
     */
    public function values(): array
    {
        return array_values($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function size(): int
    {
        return count($this->items);
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * Always non-empty.
     *
     * @param TValue $value
     * @return self<TValue>
     */
    public function put(int|string $key, mixed $value): self
    {
        return new self([...$this->items, $key => $value]);
    }

    /**
     * May become empty → returns Map.
     *
     * @return Map<TValue>
     */
    public function remove(int|string $key): Map
    {
        $items = $this->items;
        unset($items[$key]);

        return Map::fromArray($items);
    }

    /**
     * @template U
     * @param callable(TValue, array-key): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[$key] = $fn($value, $key);
        }

        return new self($result);
    }

    /**
     * May become empty → returns Map.
     *
     * @param callable(TValue, array-key): bool $predicate
     * @return Map<TValue>
     */
    public function filter(callable $predicate): Map
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            if ($predicate($value, $key)) {
                $result[$key] = $value;
            }
        }

        return Map::fromArray($result);
    }

    /**
     * @template U
     * @param U $initial
     * @param callable(U, TValue, array-key): U $fn
     * @return U
     */
    public function fold(mixed $initial, callable $fn): mixed
    {
        $accumulator = $initial;

        foreach ($this->items as $key => $value) {
            $accumulator = $fn($accumulator, $value, $key);
        }

        return $accumulator;
    }

    /**
     * @param self<TValue> $other
     * @return self<TValue>
     */
    public function merge(self $other): self
    {
        return new self([...$this->items, ...$other->items]);
    }

    /**
     * @param callable(TValue, array-key): mixed $fn
     * @return self<TValue>
     */
    public function tap(callable $fn): self
    {
        foreach ($this->items as $key => $value) {
            $fn($value, $key);
        }

        return $this;
    }

    /**
     * @return Map<TValue>
     */
    public function toMap(): Map
    {
        return Map::fromArray($this->items);
    }

    /**
     * @param self<TValue> $other
     */
    public function equals(self $other): bool
    {
        return $this->items === $other->items;
    }

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return non-empty-array<array-key, TValue>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @return Traversable<array-key, TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return non-empty-array<array-key, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}