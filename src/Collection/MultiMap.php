<?php

declare(strict_types=1);

namespace Exact\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Immutable multimap: each key maps to a non-empty collection of values.
 *
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, Vec<TValue>>
 * @implements JsonSerializable<array<TKey, list<TValue>>>
 */
final readonly class MultiMap implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param array<TKey, list<TValue>> $items
     */
    private function __construct(
        private array $items,
    ) {
    }

    // ───────────────────────── Constructors ─────────────────────────

    /** @return self<never, never> */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @template K of array-key
     * @template V
     * @param K $key
     * @param V $value
     * @return self<K, V>
     */
    public static function singleton(mixed $key, mixed $value): self
    {
        return new self([$key => [$value]]);
    }

    // ───────────────────────── Operations ─────────────────────────

    /**
     * @param TKey $key
     * @param TValue $value
     * @return self<TKey, TValue>
     */
    public function put(mixed $key, mixed $value): self
    {
        $items = $this->items;
        $items[$key] ??= [];
        $items[$key][] = $value;

        return new self($items);
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @return self<TKey, TValue>
     */
    public function removeValue(mixed $key, mixed $value): self
    {
        if (!isset($this->items[$key])) {
            return $this;
        }

        $items = $this->items;
        $items[$key] = array_values(array_filter(
            $items[$key],
            static fn (mixed $v): bool => $v !== $value,
        ));

        if ($items[$key] === []) {
            unset($items[$key]);
        }

        return new self($items);
    }

    /**
     * @param TKey $key
     * @return self<TKey, TValue>
     */
    public function removeKey(mixed $key): self
    {
        $items = $this->items;
        unset($items[$key]);

        return new self($items);
    }

    // ───────────────────────── Access ─────────────────────────

    /**
     * @param TKey $key
     * @return Vec<TValue>
     */
    public function get(mixed $key): Vec
    {
        return Vec::fromArray($this->items[$key] ?? []);
    }

    /**
     * @param TKey $key
     */
    public function has(mixed $key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * @return list<TKey>
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    /** Number of keys. */
    public function count(): int
    {
        return count($this->items);
    }

    /** Number of keys (alias). */
    public function size(): int
    {
        return count($this->items);
    }

    /** Total number of values across all keys. */
    public function totalSize(): int
    {
        $total = 0;

        foreach ($this->items as $values) {
            $total += count($values);
        }

        return $total;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * @template U
     * @param callable(TValue, TKey): U $fn
     * @return self<TKey, U>
     */
    public function map(callable $fn): self
    {
        $result = [];

        foreach ($this->items as $key => $values) {
            foreach ($values as $value) {
                $result[$key][] = $fn($value, $key);
            }
        }

        return new self($result);
    }

    /**
     * @param callable(TValue, TKey): bool $predicate
     * @return self<TKey, TValue>
     */
    public function filter(callable $predicate): self
    {
        $result = [];

        foreach ($this->items as $key => $values) {
            foreach ($values as $value) {
                if ($predicate($value, $key)) {
                    $result[$key][] = $value;
                }
            }
        }

        return new self($result);
    }

    /**
     * @template U
     * @param U $initial
     * @param callable(U, TValue, TKey): U $fn
     * @return U
     */
    public function fold(mixed $initial, callable $fn): mixed
    {
        $accumulator = $initial;

        foreach ($this->items as $key => $values) {
            foreach ($values as $value) {
                $accumulator = $fn($accumulator, $value, $key);
            }
        }

        return $accumulator;
    }

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return array<TKey, list<TValue>>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @return Traversable<TKey, Vec<TValue>>
     */
    public function getIterator(): Traversable
    {
        $result = [];

        foreach ($this->items as $key => $values) {
            $result[$key] = Vec::fromArray($values);
        }

        return new ArrayIterator($result);
    }

    /**
     * @return array<TKey, list<TValue>>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}