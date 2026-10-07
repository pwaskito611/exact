<?php

declare(strict_types=1);

namespace Exact\Type\Tuple;

use JsonSerializable;

/**
 * Immutable heterogeneous two-element value object.
 *
 * @template T1
 * @template T2
 * @implements JsonSerializable<array{first: T1, second: T2}>
 */
final readonly class Pair implements JsonSerializable
{
    /**
     * @param T1 $first
     * @param T2 $second
     */
    private function __construct(
        private mixed $first,
        private mixed $second,
    ) {
    }

    /**
     * @template A
     * @template B
     * @param A $first
     * @param B $second
     * @return self<A, B>
     */
    public static function of(mixed $first, mixed $second): self
    {
        return new self($first, $second);
    }

    /**
     * @return T1
     */
    public function first(): mixed
    {
        return $this->first;
    }

    /**
     * @return T2
     */
    public function second(): mixed
    {
        return $this->second;
    }

    /**
     * @return self<T2, T1>
     */
    public function swap(): self
    {
        return new self($this->second, $this->first);
    }

    /**
     * Transform the first element.
     *
     * @template U
     * @param callable(T1): U $fn
     * @return self<U, T2>
     */
    public function mapFirst(callable $fn): self
    {
        return new self($fn($this->first), $this->second);
    }

    /**
     * Transform the second element.
     *
     * @template U
     * @param callable(T2): U $fn
     * @return self<T1, U>
     */
    public function mapSecond(callable $fn): self
    {
        return new self($this->first, $fn($this->second));
    }

    /**
     * Transform both elements at once.
     *
     * @template U
     * @template V
     * @param callable(T1): U $onFirst
     * @param callable(T2): V $onSecond
     * @return self<U, V>
     */
    public function bimap(callable $onFirst, callable $onSecond): self
    {
        return new self(
            $onFirst($this->first),
            $onSecond($this->second),
        );
    }

    /**
     * Reduce the pair into a single value.
     *
     * Equivalent to Haskell's uncurry.
     *
     * @template U
     * @param callable(T1, T2): U $fn
     * @return U
     */
    public function fold(callable $fn): mixed
    {
        return $fn($this->first, $this->second);
    }

    /**
     * @return array{0: T1, 1: T2}
     */
    public function toArray(): array
    {
        return [$this->first, $this->second];
    }

    /**
     * @param self<T1, T2> $other
     */
    public function equals(self $other): bool
    {
        return $this->first === $other->first
            && $this->second === $other->second;
    }

    /**
     * @return array{first: T1, second: T2}
     */
    public function jsonSerialize(): array
    {
        return [
            'first' => $this->first,
            'second' => $this->second,
        ];
    }
}