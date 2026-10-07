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
 * Immutable bidirectional map. Both keys and values must be unique;
 * every key maps to exactly one value, and vice versa.
 *
 * @template TKey of array-key
 * @template TValue of array-key
 * @implements IteratorAggregate<TKey, TValue>
 * @implements JsonSerializable<array<TKey, TValue>>
 */
final readonly class BiMap implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param array<TKey, TValue> $forward   key → value
     * @param array<TValue, TKey> $backward  value → key
     */
    private function __construct(
        private array $forward,
        private array $backward,
    ) {
    }

    // ───────────────────────── Constructors ─────────────────────────

    /** @return self<never, never> */
    public static function empty(): self
    {
        return new self([], []);
    }

    /**
     * @template K of array-key
     * @template V of array-key
     * @param K $key
     * @param V $value
     * @return self<K, V>
     */
    public static function of(mixed $key, mixed $value): self
    {
        return self::empty()->put($key, $value);
    }

    // ───────────────────────── Operations ─────────────────────────

    /**
     * @param TKey $key
     * @param TValue $value
     * @return self<TKey, TValue>
     * @throws InvalidArgumentException if $key or $value already mapped differently
     */
    public function put(mixed $key, mixed $value): self
    {
        $existingValue = $this->forward[$key] ?? null;

        if ($existingValue !== null && $existingValue !== $value) {
            throw new InvalidArgumentException(
                'Key already mapped to a different value: '
                . var_export($key, true)
            );
        }

        $existingKey = $this->backward[$value] ?? null;

        if ($existingKey !== null && $existingKey !== $key) {
            throw new InvalidArgumentException(
                'Value already mapped to a different key: '
                . var_export($value, true)
            );
        }

        if ($existingValue !== null) {
            return $this;
        }

        return new self(
            [...$this->forward, $key => $value],
            [...$this->backward, $value => $key],
        );
    }

    /**
     * @param TKey $key
     * @return self<TKey, TValue>
     */
    public function removeKey(mixed $key): self
    {
        if (!isset($this->forward[$key])) {
            return $this;
        }

        $forward = $this->forward;
        $backward = $this->backward;
        $value = $forward[$key];

        unset($forward[$key], $backward[$value]);

        return new self($forward, $backward);
    }

    /**
     * @param TValue $value
     * @return self<TKey, TValue>
     */
    public function removeValue(mixed $value): self
    {
        if (!isset($this->backward[$value])) {
            return $this;
        }

        return $this->removeKey($this->backward[$value]);
    }

    // ───────────────────────── Access ─────────────────────────

    /**
     * @param TKey $key
     * @return TValue
     * @throws OutOfBoundsException
     */
    public function get(mixed $key): mixed
    {
        if (!isset($this->forward[$key])) {
            throw new OutOfBoundsException(
                'Key not found: ' . var_export($key, true)
            );
        }

        return $this->forward[$key];
    }

    /**
     * @param TValue $value
     * @return TKey
     * @throws OutOfBoundsException
     */
    public function getKey(mixed $value): mixed
    {
        if (!isset($this->backward[$value])) {
            throw new OutOfBoundsException(
                'Value not found: ' . var_export($value, true)
            );
        }

        return $this->backward[$value];
    }

    /**
     * @param TKey $key
     * @return TValue|null
     */
    public function getOrNull(mixed $key): mixed
    {
        return $this->forward[$key] ?? null;
    }

    /**
     * @param TValue $value
     * @return TKey|null
     */
    public function getKeyOrNull(mixed $value): mixed
    {
        return $this->backward[$value] ?? null;
    }

    /**
     * @param TKey $key
     */
    public function has(mixed $key): bool
    {
        return isset($this->forward[$key]);
    }

    /**
     * @param TValue $value
     */
    public function hasValue(mixed $value): bool
    {
        return isset($this->backward[$value]);
    }

    /**
     * @return list<TKey>
     */
    public function keys(): array
    {
        return array_keys($this->forward);
    }

    /**
     * @return list<TValue>
     */
    public function values(): array
    {
        return array_values($this->forward);
    }

    public function count(): int
    {
        return count($this->forward);
    }

    public function size(): int
    {
        return count($this->forward);
    }

    public function isEmpty(): bool
    {
        return $this->forward === [];
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * Swap keys and values.
     *
     * @return self<TValue, TKey>
     */
    public function inverse(): self
    {
        return new self($this->backward, $this->forward);
    }

    /**
     * @param self<TKey, TValue> $other
     */
    public function equals(self $other): bool
    {
        return $this->forward === $other->forward;
    }

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->forward;
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->forward);
    }

    /**
     * @return array<TKey, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->forward;
    }
}