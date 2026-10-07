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
 * Immutable key-value map.
 *
 * Keys are int|string (PHP array-key). Values may be of any type.
 * Use fromPairs() with Exact\Type\Tuple\Pair when you already have pairs.
 *
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 * @implements JsonSerializable<array<TKey, TValue>>
 */
final readonly class Map implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param array<TKey, TValue> $items
     */
    private function __construct(
        private array $items,
    ) {
    }

    // ───────────────────────── Constructors ─────────────────────────

    /**
     * @return self<never, never>
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Variadic key-value constructor: Map::of('a', 1, 'b', 2).
     *
     * @template K of array-key
     * @template V
     * @param K|V ...$items
     * @return self<K, V>
     */
    public static function of(mixed ...$items): self
    {
        if (count($items) % 2 !== 0) {
            throw new InvalidArgumentException(
                'Map::of() requires an even number of key-value arguments.'
            );
        }

        $map = [];

        for ($i = 0, $n = count($items); $i < $n; $i += 2) {
            $map[$items[$i]] = $items[$i + 1];
        }

        return new self($map);
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
        return new self([$key => $value]);
    }

    /**
     * @template K of array-key
     * @template V
     * @param array<K, V> $items
     * @return self<K, V>
     */
    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    /**
     * @template K of array-key
     * @template V
     * @param iterable<K, V> $items
     * @return self<K, V>
     */
    public static function fromIterable(iterable $items): self
    {
        if (is_array($items)) {
            return new self($items);
        }

        return new self(iterator_to_array($items));
    }

    // ───────────────────────── Query ─────────────────────────

    /**
     * @param TKey $key
     */
    public function has(mixed $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * @param TKey $key
     */
    public function missing(mixed $key): bool
    {
        return !$this->has($key);
    }

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
     * @param TKey $key
     * @return TValue
     * @throws OutOfBoundsException
     */
    public function get(mixed $key): mixed
    {
        if (!array_key_exists($key, $this->items)) {
            throw new OutOfBoundsException(
                'Key not found: ' . var_export($key, true)
            );
        }

        return $this->items[$key];
    }

    /**
     * @param TKey $key
     * @return TValue|null
     */
    public function getOrNull(mixed $key): mixed
    {
        return array_key_exists($key, $this->items)
            ? $this->items[$key]
            : null;
    }

    /**
     * @template D
     * @param TKey $key
     * @param D $default
     * @return TValue|D
     */
    public function getOrElse(mixed $key, mixed $default): mixed
    {
        return array_key_exists($key, $this->items)
            ? $this->items[$key]
            : $default;
    }

    /**
     * @template D
     * @param TKey $key
     * @param callable(): D $default
     * @return TValue|D
     */
    public function getOrElseGet(mixed $key, callable $default): mixed
    {
        return array_key_exists($key, $this->items)
            ? $this->items[$key]
            : $default();
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @return self<TKey, TValue>
     */
    public function put(mixed $key, mixed $value): self
    {
        return new self([...$this->items, $key => $value]);
    }

    /**
     * @param TKey $key
     * @return self<TKey, TValue>
     */
    public function remove(mixed $key): self
    {
        $items = $this->items;
        unset($items[$key]);

        return new self($items);
    }

    // ───────────────────────── Iteration ─────────────────────────

    /**
     * @return list<TKey>
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
     * @return list<array{0: TKey, 1: TValue}>
     */
    public function entries(): array
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[] = [$key, $value];
        }

        return $result;
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

        foreach ($this->items as $key => $value) {
            $result[$key] = $fn($value, $key);
        }

        return new self($result);
    }

    /**
     * @template K of array-key
     * @param callable(TKey, TValue): K $fn
     * @return self<K, TValue>
     */
    public function mapKeys(callable $fn): self
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[$fn($key, $value)] = $value;
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

        foreach ($this->items as $key => $value) {
            if ($predicate($value, $key)) {
                $result[$key] = $value;
            }
        }

        return new self($result);
    }

    /**
     * @param callable(TKey, TValue): bool $predicate
     * @return self<TKey, TValue>
     */
    public function filterKeys(callable $predicate): self
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            if ($predicate($key, $value)) {
                $result[$key] = $value;
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

        foreach ($this->items as $key => $value) {
            $accumulator = $fn($accumulator, $value, $key);
        }

        return $accumulator;
    }

    /**
     * @param self<TKey, TValue> $other
     * @return self<TKey, TValue>
     */
    public function merge(self $other): self
    {
        return new self([...$this->items, ...$other->items]);
    }

    /**
     * @param callable(TValue, TKey): mixed $fn
     * @return self<TKey, TValue>
     */
    public function tap(callable $fn): self
    {
        foreach ($this->items as $key => $value) {
            $fn($value, $key);
        }

        return $this;
    }

    /**
     * @param self<TKey, TValue> $other
     */
    public function equals(self $other): bool
    {
        if (count($this->items) !== count($other->items)) {
            return false;
        }

        foreach ($this->items as $key => $value) {
            if (!array_key_exists($key, $other->items)) {
                return false;
            }

            if ($other->items[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<TKey, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}