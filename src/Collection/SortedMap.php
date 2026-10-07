<?php

declare(strict_types=1);

namespace Exact\Collection;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use Traversable;

/**
 * Immutable sorted map. Keys must be int|string (PHP array-key).
 * Entries are kept sorted by key per the comparator.
 *
 * @template TValue
 * @implements IteratorAggregate<array-key, TValue>
 * @implements JsonSerializable<array<array-key, TValue>>
 */
final readonly class SortedMap implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var callable(int|string, int|string): int */
    private Closure $comparator;

    /**
     * @param array<array-key, TValue> $items  // sorted by key
     * @param callable(int|string, int|string): int $comparator
     */
    private function __construct(
        private array $items,
        callable $comparator,
    ) {
        $this->comparator = Closure::fromCallable($comparator);
    }

    // ───────────────────────── Constructors ─────────────────────────

    /** @return self<never> */
    public static function empty(?callable $comparator = null): self
    {
        return new self([], $comparator ?? self::defaultComparator());
    }

    /**
     * @template V
     * @param array<array-key, V> $items
     * @param callable(int|string, int|string): int $comparator
     * @return self<V>
     */
    public static function fromArray(array $items, callable $comparator): self
    {
        $instance = new self([], $comparator);
        $result = $instance;

        foreach ($items as $key => $value) {
            $result = $result->put($key, $value);
        }

        return $result;
    }

    /**
     * @template V
     * @param iterable<array-key, V> $items
     * @param callable(int|string, int|string): int $comparator
     * @return self<V>
     */
    public static function fromIterable(iterable $items, callable $comparator): self
    {
        $instance = new self([], $comparator);
        $result = $instance;

        foreach ($items as $key => $value) {
            $result = $result->put($key, $value);
        }

        return $result;
    }

    // ───────────────────────── Operations ─────────────────────────

    /**
     * @param TValue $value
     * @return self<TValue>
     */
    public function put(int|string $key, mixed $value): self
    {
        $items = $this->items;
        $items[$key] = $value;
        uksort($items, $this->comparator);

        return new self($items, $this->comparator);
    }

    /**
     * @return self<TValue>
     */
    public function remove(int|string $key): self
    {
        if (!array_key_exists($key, $this->items)) {
            return $this;
        }

        $items = $this->items;
        unset($items[$key]);

        return new self($items, $this->comparator);
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

    /**
     * @template D
     * @param D $default
     * @return TValue|D
     */
    public function getOrElse(int|string $key, mixed $default): mixed
    {
        return array_key_exists($key, $this->items)
            ? $this->items[$key]
            : $default;
    }

    public function has(int|string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * @return int|string|null
     */
    public function firstKey(): mixed
    {
        $keys = array_keys($this->items);

        return $keys[0] ?? null;
    }

    /**
     * @return int|string|null
     */
    public function lastKey(): mixed
    {
        $keys = array_keys($this->items);

        return $keys === [] ? null : $keys[count($keys) - 1];
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

    // ───────────────────────── Iteration ─────────────────────────

    /**
     * @return list<int|string>
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

    /**
     * @return list<array{0: int|string, 1: TValue}>
     */
    public function entries(): array
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[] = [$key, $value];
        }

        return $result;
    }

    /**
     * @param int|string $low
     * @param int|string $high
     * @return self<TValue>
     */
    public function range(int|string $low, int|string $high): self
    {
        $cmp = $this->comparator;
        $result = $this->empty($this->comparator);

        foreach ($this->items as $key => $value) {
            if ($cmp($key, $low) >= 0 && $cmp($key, $high) <= 0) {
                $result = $result->put($key, $value);
            }
        }

        return $result;
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * @template U
     * @param callable(TValue, int|string): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[$key] = $fn($value, $key);
        }

        return new self($result, $this->comparator);
    }

    /**
     * @param callable(int|string, TValue): int|string $fn
     * @return self<TValue>
     */
    public function mapKeys(callable $fn): self
    {
        $instance = $this->empty($this->comparator);

        foreach ($this->items as $key => $value) {
            $instance = $instance->put($fn($key, $value), $value);
        }

        return $instance;
    }

    /**
     * @param callable(TValue, int|string): bool $predicate
     * @return self<TValue>
     */
    public function filter(callable $predicate): self
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            if ($predicate($value, $key)) {
                $result[$key] = $value;
            }
        }

        return new self($result, $this->comparator);
    }

    /**
     * @template U
     * @param U $initial
     * @param callable(U, TValue, int|string): U $fn
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
        $result = $this;

        foreach ($other->items as $key => $value) {
            $result = $result->put($key, $value);
        }

        return $result;
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
     * @return array<array-key, TValue>
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
     * @return array<array-key, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    // ───────────────────────── Internals ─────────────────────────

    /**
     * @return callable(int|string, int|string): int
     */
    private static function defaultComparator(): callable
    {
        return static fn (int|string $a, int|string $b): int => $a <=> $b;
    }
}