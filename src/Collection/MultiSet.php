<?php

declare(strict_types=1);

namespace Exact\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Immutable multiset (bag) with per-element multiplicity.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 * @implements JsonSerializable<array<string, array{value: mixed, count: int}>>
 */
final readonly class MultiSet implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param array<string, array{value: mixed, count: int}> $entries
     */
    private function __construct(
        private array $entries,
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
        $result = self::empty();

        foreach ($items as $item) {
            $result = $result->add($item);
        }

        return $result;
    }

    /**
     * @template U
     * @param iterable<U> $items
     * @return self<U>
     */
    public static function fromIterable(iterable $items): self
    {
        $result = self::empty();

        foreach ($items as $item) {
            $result = $result->add($item);
        }

        return $result;
    }

    // ───────────────────────── Operations ─────────────────────────

    /**
     * @param T $value
     * @return self<T>
     */
    public function add(mixed $value, int $times = 1): self
    {
        if ($times < 0) {
            throw new \InvalidArgumentException('Times must be non-negative.');
        }

        if ($times === 0) {
            return $this;
        }

        $key = $this->key($value);
        $entries = $this->entries;

        if (isset($entries[$key])) {
            $entries[$key]['count'] += $times;
        } else {
            $entries[$key] = ['value' => $value, 'count' => $times];
        }

        return new self($entries);
    }

    /**
     * @param T $value
     * @return self<T>
     */
    public function remove(mixed $value, int $times = 1): self
    {
        $key = $this->key($value);

        if (!isset($this->entries[$key])) {
            return $this;
        }

        $entries = $this->entries;
        $entries[$key]['count'] -= $times;

        if ($entries[$key]['count'] <= 0) {
            unset($entries[$key]);
        }

        return new self($entries);
    }

    /**
     * @param T $value
     */
    public function countOf(mixed $value): int
    {
        return $this->entries[$this->key($value)]['count'] ?? 0;
    }

    /**
     * Alias for countOf().
     *
     * @param T $value
     */
    public function multiplicity(mixed $value): int
    {
        return $this->countOf($value);
    }

    /**
     * @param T $value
     */
    public function contains(mixed $value): bool
    {
        return isset($this->entries[$this->key($value)]);
    }

    /** Total number of elements including duplicates. */
    public function count(): int
    {
        $total = 0;

        foreach ($this->entries as $entry) {
            $total += $entry['count'];
        }

        return $total;
    }

    /** Number of distinct elements. */
    public function distinctCount(): int
    {
        return count($this->entries);
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    // ───────────────────────── Set algebra ─────────────────────────

    /**
     * @param self<T> $other
     * @return self<T>
     */
    public function union(self $other): self
    {
        return $this->addAll($other);
    }

    /**
     * @param self<T> $other
     * @return self<T>
     */
    public function addAll(self $other): self
    {
        $result = $this;

        foreach ($other->entries as $key => $entry) {
            $result = $result->add($entry['value'], $entry['count']);
        }

        return $result;
    }

    /**
     * @param self<T> $other
     * @return self<T>
     */
    public function intersect(self $other): self
    {
        $result = self::empty();

        foreach ($this->entries as $key => $entry) {
            if (isset($other->entries[$key])) {
                $result = $result->add(
                    $entry['value'],
                    min($entry['count'], $other->entries[$key]['count'])
                );
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
        $result = $this;

        foreach ($other->entries as $key => $entry) {
            if (isset($result->entries[$key])) {
                $result = $result->remove($entry['value'], $entry['count']);
            }
        }

        return $result;
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        $result = self::empty();

        foreach ($this->entries as $entry) {
            for ($i = 0; $i < $entry['count']; $i++) {
                $result = $result->add($fn($entry['value']));
            }
        }

        return $result;
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        $result = self::empty();

        foreach ($this->entries as $entry) {
            if ($predicate($entry['value'])) {
                $result = $result->add($entry['value'], $entry['count']);
            }
        }

        return $result;
    }

    /**
     * @return Set<T>
     */
    public function distinct(): Set
    {
        return Set::fromArray(array_map(
            static fn (array $entry): mixed => $entry['value'],
            array_values($this->entries),
        ));
    }

    /**
     * @param self<T> $other
     */
    public function equals(self $other): bool
    {
        if (count($this->entries) !== count($other->entries)) {
            return false;
        }

        foreach ($this->entries as $key => $entry) {
            if (!isset($other->entries[$key])) {
                return false;
            }

            if ($other->entries[$key]['count'] !== $entry['count']) {
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
        $result = [];

        foreach ($this->entries as $entry) {
            for ($i = 0; $i < $entry['count']; $i++) {
                $result[] = $entry['value'];
            }
        }

        return $result;
    }

    /**
     * @return list<T>
     */
    public function distinctValues(): array
    {
        return array_map(
            static fn (array $entry): mixed => $entry['value'],
            array_values($this->entries),
        );
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * @return array<string, array{value: mixed, count: int}>
     */
    public function jsonSerialize(): array
    {
        return $this->entries;
    }

    // ───────────────────────── Internals ─────────────────────────

    private function key(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return gettype($value) . ':' . (string) $value;
        }

        return 'object:' . serialize($value);
    }
}