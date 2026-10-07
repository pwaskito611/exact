<?php

declare(strict_types=1);

namespace Exact\Type\Tuple;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use LogicException;
use OutOfBoundsException;
use Traversable;

/**
 * Immutable fixed-arity product type.
 *
 * A Tuple holds a small number of values whose *position* carries meaning.
 * Unlike Vec, the values may be heterogeneous and the arity is fixed.
 *
 * For homogeneous, variable-length collections use Exact\Collection\Vec.
 *
 * @template T1
 * @template T2 = never
 * @template T3 = never
 * @implements IteratorAggregate<int, T1|T2|T3>
 * @implements JsonSerializable<list<T1|T2|T3>>
 */
final readonly class Tuple implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param list<T1|T2|T3> $values
     */
    private function __construct(
        private array $values,
    ) {
    }

    // ───────────────────────── Constructors ─────────────────────────

    /**
     * @template A
     * @param A $value
     * @return self<A, never, never>
     */
    public static function of1(mixed $value): self
    {
        return new self([$value]);
    }

    /**
     * @template A
     * @template B
     * @param A $first
     * @param B $second
     * @return self<A, B, never>
     */
    public static function of2(mixed $first, mixed $second): self
    {
        return new self([$first, $second]);
    }

    /**
     * @template A
     * @template B
     * @template C
     * @param A $first
     * @param B $second
     * @param C $third
     * @return self<A, B, C>
     */
    public static function of3(mixed $first, mixed $second, mixed $third): self
    {
        return new self([$first, $second, $third]);
    }

    /**
     * Variadic escape hatch. Prefer of1/of2/of3 for type inference.
     *
     * @param mixed ...$values
     */
    public static function of(mixed ...$values): self
    {
        return new self(array_values($values));
    }

    /**
     * @template A
     * @template B
     * @param A $first
     * @param B $second
     * @return self<A, B, never>
     */
    public static function pair(mixed $first, mixed $second): self
    {
        return self::of2($first, $second);
    }

    /**
     * @template A
     * @template B
     * @template C
     * @param A $first
     * @param B $second
     * @param C $third
     * @return self<A, B, C>
     */
    public static function triple(mixed $first, mixed $second, mixed $third): self
    {
        return self::of3($first, $second, $third);
    }

    // ───────────────────────── Accessors ─────────────────────────

    /**
     * @return T1
     * @throws OutOfBoundsException
     */
    public function first(): mixed
    {
        return $this->at(0);
    }

    /**
     * @return T2
     * @throws OutOfBoundsException
     */
    public function second(): mixed
    {
        return $this->at(1);
    }

    /**
     * @return T3
     * @throws OutOfBoundsException
     */
    public function third(): mixed
    {
        return $this->at(2);
    }

    /**
     * @return T1|T2|T3
     * @throws OutOfBoundsException
     */
    public function last(): mixed
    {
        return $this->at($this->count() - 1);
    }

    /**
     * @return T1|T2|T3
     * @throws OutOfBoundsException
     */
    public function at(int $index): mixed
    {
        if ($index < 0 || $index >= $this->count()) {
            throw new OutOfBoundsException(
                "Index {$index} out of range [0, {$this->count()})."
            );
        }

        return $this->values[$index];
    }

    /**
     * @return T1|T2|T3|null
     */
    public function atOrNull(int $index): mixed
    {
        return $this->values[$index] ?? null;
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * @template U
     * @param callable(T1): U $fn
     * @return self<U, T2, T3>
     */
    public function mapFirst(callable $fn): self
    {
        return $this->mapAt(0, $fn);
    }

    /**
     * @template U
     * @param callable(T2): U $fn
     * @return self<T1, U, T3>
     */
    public function mapSecond(callable $fn): self
    {
        return $this->mapAt(1, $fn);
    }

    /**
     * @template U
     * @param callable(T3): U $fn
     * @return self<T1, T2, U>
     */
    public function mapThird(callable $fn): self
    {
        return $this->mapAt(2, $fn);
    }

    /**
     * @template U
     * @param callable(T1|T2|T3): U $fn
     * @return self
     */
    public function mapAt(int $index, callable $fn): self
    {
        if ($index < 0 || $index >= $this->count()) {
            throw new OutOfBoundsException(
                "Cannot map index {$index}: out of range."
            );
        }

        $values = $this->values;
        $values[$index] = $fn($values[$index]);

        return new self($values);
    }

    /**
     * Transform the first two elements with two different functions.
     *
     * @template U
     * @template V
     * @param callable(T1): U $onFirst
     * @param callable(T2): V $onSecond
     * @return self<U, V, T3>
     */
    public function bimap(callable $onFirst, callable $onSecond): self
    {
        if ($this->count() < 2) {
            throw new LogicException(
                'bimap() requires a tuple of at least 2 elements.'
            );
        }

        $values = $this->values;
        $values[0] = $onFirst($values[0]);
        $values[1] = $onSecond($values[1]);

        return new self($values);
    }

    /**
     * Swap the first two elements.
     *
     * @return self<T2, T1, T3>
     */
    public function swap(): self
    {
        if ($this->count() < 2) {
            throw new LogicException(
                'swap() requires a tuple of at least 2 elements.'
            );
        }

        $values = $this->values;
        [$values[0], $values[1]] = [$values[1], $values[0]];

        return new self($values);
    }

    // ───────────────────────── Reduction ─────────────────────────

    /**
     * Unpack all elements into a single callable.
     *
     * Haskell's uncurry, generalised to any arity.
     *
     * @template U
     * @param callable(mixed ...): U $fn
     * @return U
     */
    public function fold(callable $fn): mixed
    {
        return $fn(...$this->values);
    }

    // ───────────────────────── Comparison ─────────────────────────

    /**
     * @param self<T1, T2, T3> $other
     */
    public function equals(self $other): bool
    {
        return $this->values === $other->values;
    }

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return list<T1|T2|T3>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * @return Traversable<int, T1|T2|T3>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    /**
     * @return list<T1|T2|T3>
     */
    public function jsonSerialize(): array
    {
        return $this->values;
    }
}