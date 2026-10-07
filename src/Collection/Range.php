<?php

declare(strict_types=1);

namespace Exact\Collection;

use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Lazy integer range: start, start+step, start+2*step, ...
 *
 * Iteration is lazy. toArray() materializes all values — never call it
 * on an unbounded Range (use take() first).
 *
 * @implements IteratorAggregate<int, int>
 * @implements JsonSerializable<list<int>>
 */
final readonly class Range implements IteratorAggregate, JsonSerializable
{
    private function __construct(
        private int $start,
        private int $end,
        private int $step,
    ) {
        if ($step === 0) {
            throw new InvalidArgumentException('Range step cannot be zero.');
        }
    }

    // ───────────────────────── Constructors ─────────────────────────

    public static function of(int $start, int $end, int $step = 1): self
    {
        if ($step === 0) {
            throw new InvalidArgumentException('Range step cannot be zero.');
        }

        return new self($start, $end, $step);
    }

    public static function closed(int $start, int $end): self
    {
        // Inclusive on both ends: 1..5 → [1, 2, 3, 4, 5]
        return $start <= $end
            ? new self($start, $end + 1, 1)
            : new self($start, $end - 1, -1);
    }

    // ───────────────────────── Query ─────────────────────────

    public function start(): int
    {
        return $this->start;
    }

    public function end(): int
    {
        return $this->end;
    }

    public function step(): int
    {
        return $this->step;
    }

    public function isEmpty(): bool
    {
        if ($this->step > 0) {
            return $this->start >= $this->end;
        }

        return $this->start <= $this->end;
    }

    public function count(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }

        return (int) ceil(abs($this->end - $this->start) / abs($this->step));
    }

    public function contains(int $value): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        if ($this->step > 0) {
            if ($value < $this->start || $value >= $this->end) {
                return false;
            }
        } else {
            if ($value > $this->start || $value <= $this->end) {
                return false;
            }
        }

        return ($value - $this->start) % $this->step === 0;
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * @return LazySeq<int>
     */
    public function take(int $count): LazySeq
    {
        return LazySeq::from($this)->take($count);
    }

    /**
     * @return LazySeq<int>
     */
    public function drop(int $count): LazySeq
    {
        return LazySeq::from($this)->drop($count);
    }

    /**
     * @param callable(int): bool $predicate
     * @return LazySeq<int>
     */
    public function filter(callable $predicate): LazySeq
    {
        return LazySeq::from($this)->filter($predicate);
    }

    /**
     * @template U
     * @param callable(int): U $fn
     * @return LazySeq<U>
     */
    public function map(callable $fn): LazySeq
    {
        return LazySeq::from($this)->map($fn);
    }

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return list<int>
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }

    /**
     * @return Generator<int, int>
     */
    public function getIterator(): Traversable
    {
        if ($this->step > 0) {
            for ($i = $this->start; $i < $this->end; $i += $this->step) {
                yield $i;
            }
        } else {
            for ($i = $this->start; $i > $this->end; $i += $this->step) {
                yield $i;
            }
        }
    }

    /**
     * @return list<int>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}