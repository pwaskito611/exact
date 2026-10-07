<?php

declare(strict_types=1);

namespace Exact\Collection;

use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use Traversable;

/**
 * Immutable binary-heap priority queue.
 *
 * Push and pop are O(log n). Peek is O(1). Lower priority value pops
 * first by default (min-heap); pass a custom comparator for max-heap
 * or custom ordering.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 * @implements JsonSerializable<list<T>>
 */
final readonly class PriorityQueue implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var callable(T, T): int */
    private Closure $comparator;

    /**
     * @param list<T> $heap  // binary heap array
     * @param callable(T, T): int $comparator
     */
    private function __construct(
        private array $heap,
        callable $comparator,
    ) {
        $this->comparator = Closure::fromCallable($comparator);
    }

    // ───────────────────────── Constructors ─────────────────────────

    /** @return self<never> */
    public static function empty(?callable $comparator = null): self
    {
        return new self([], $comparator ?? self::minComparator());
    }

    /**
     * @template U
     * @param iterable<U> $items
     * @param callable(U, U): int|null $comparator
     * @return self<U>
     */
    public static function fromIterable(iterable $items, ?callable $comparator = null): self
    {
        $instance = new self([], $comparator ?? self::minComparator());
        $result = $instance;

        foreach ($items as $item) {
            $result = $result->push($item);
        }

        return $result;
    }

    /**
     * @template U
     * @param list<U> $items
     * @param callable(U, U): int|null $comparator
     * @return self<U>
     */
    public static function fromArray(array $items, ?callable $comparator = null): self
    {
        return self::fromIterable($items, $comparator);
    }

    // ───────────────────────── Operations ─────────────────────────

    /**
     * @param T $value
     * @return self<T>
     */
    public function push(mixed $value): self
    {
        $heap = $this->heap;
        $heap[] = $value;

        $index = count($heap) - 1;
        $cmp = $this->comparator;

        while ($index > 0) {
            $parent = intdiv($index - 1, 2);

            if ($cmp($heap[$index], $heap[$parent]) >= 0) {
                break;
            }

            [$heap[$index], $heap[$parent]] = [$heap[$parent], $heap[$index]];
            $index = $parent;
        }

        return new self($heap, $this->comparator);
    }

    /**
     * @return array{0: T, 1: self<T>}
     * @throws OutOfBoundsException
     */
    public function pop(): array
    {
        if ($this->heap === []) {
            throw new OutOfBoundsException('Cannot pop from an empty PriorityQueue.');
        }

        $top = $this->heap[0];
        $heap = $this->heap;
        $last = array_pop($heap);

        if ($heap === []) {
            return [$top, new self([], $this->comparator)];
        }

        $heap[0] = $last;
        $this->siftDown($heap, 0);

        return [$top, new self($heap, $this->comparator)];
    }

    /**
     * @return T
     * @throws OutOfBoundsException
     */
    public function peek(): mixed
    {
        if ($this->heap === []) {
            throw new OutOfBoundsException('Cannot peek at an empty PriorityQueue.');
        }

        return $this->heap[0];
    }

    /**
     * @return T|null
     */
    public function peekOrNull(): mixed
    {
        return $this->heap[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->heap === [];
    }

    public function count(): int
    {
        return count($this->heap);
    }

    public function size(): int
    {
        return count($this->heap);
    }

    /**
     * @return list<T>
     */
    public function toSortedArray(): array
    {
        $queue = $this;
        $result = [];

        while (!$queue->isEmpty()) {
            [$value, $queue] = $queue->pop();
            $result[] = $value;
        }

        return $result;
    }

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        // Iterate in heap order (not sorted order).
        foreach ($this->heap as $value) {
            yield $value;
        }
    }

    /**
     * @return list<T>
     */
    public function jsonSerialize(): array
    {
        return $this->heap;
    }

    // ───────────────────────── Internals ─────────────────────────

    /**
     * @param list<T> $heap
     */
    private function siftDown(array &$heap, int $index): void
    {
        $cmp = $this->comparator;
        $size = count($heap);

        while (true) {
            $left = 2 * $index + 1;
            $right = 2 * $index + 2;
            $smallest = $index;

            if ($left < $size && $cmp($heap[$left], $heap[$smallest]) < 0) {
                $smallest = $left;
            }

            if ($right < $size && $cmp($heap[$right], $heap[$smallest]) < 0) {
                $smallest = $right;
            }

            if ($smallest === $index) {
                break;
            }

            [$heap[$index], $heap[$smallest]] = [$heap[$smallest], $heap[$index]];
            $index = $smallest;
        }
    }

    /** @return callable(mixed, mixed): int */
    private static function minComparator(): callable
    {
        return static fn (mixed $a, mixed $b): int => $a <=> $b;
    }
}