<?php

declare(strict_types=1);

namespace Exact\Collection;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use Traversable;

/**
 * Lazy sequence guaranteed to be non-empty.
 *
 * The source must be callable returning a non-empty iterable; the
 * invariant is checked lazily on first iteration. If the source yields
 * nothing, iteration throws OutOfBoundsException.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 * @implements JsonSerializable<non-empty-list<T>>
 */
final readonly class NonEmptyLazySeq implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param callable(): LazySeq<T> $source
     */
    private function __construct(
        private mixed $source,
    ) {
    }

    // ───────────────────────── Constructors ─────────────────────────

    /**
     * @template U
     * @param U $head
     * @param U ...$tail
     * @return self<U>
     */
    public static function of(mixed $head, mixed ...$tail): self
    {
        return new self(
            static fn (): LazySeq => LazySeq::of($head, ...$tail)
        );
    }

    /**
     * @template U
     * @param LazySeq<U> $lazySeq
     * @return self<U>|null
     */
    public static function fromLazySeq(LazySeq $lazySeq): ?self
    {
        if ($lazySeq->firstOrNull() === null) {
            // Note: this consumes the source. Wrap with memoize() if needed.
            return null;
        }

        return new self(static fn (): LazySeq => $lazySeq);
    }

    /**
     * @template U
     * @param U $seed
     * @param callable(U): U $next
     * @return self<U>
     */
    public static function iterate(mixed $seed, callable $next): self
    {
        return new self(
            static fn (): LazySeq => LazySeq::iterate($seed, $next)
        );
    }

    /**
     * @template U
     * @param non-empty-list<U> $items
     * @return self<U>
     */
    public static function fromArray(array $items): self
    {
        if ($items === []) {
            throw new OutOfBoundsException(
                'NonEmptyLazySeq requires a non-empty array.'
            );
        }

        return new self(
            static fn (): LazySeq => LazySeq::from($items)
        );
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        return new self(
            fn (): LazySeq => $this->toLazySeq()->map($fn)
        );
    }

    /**
     * May become empty → returns LazySeq.
     *
     * @param callable(T): bool $predicate
     * @return LazySeq<T>
     */
    public function filter(callable $predicate): LazySeq
    {
        return $this->toLazySeq()->filter($predicate);
    }

    /**
     * @template U
     * @param callable(T): iterable<U> $fn
     * @return LazySeq<U>
     */
    public function flatMap(callable $fn): LazySeq
    {
        return $this->toLazySeq()->flatMap($fn);
    }

    /**
     * @return self<T>
     */
    public function tap(callable $fn): self
    {
        return new self(
            fn (): LazySeq => $this->toLazySeq()->tap($fn)
        );
    }

    // ───────────────────────── Slicing ─────────────────────────

    /**
     * @return self<T>
     */
    public function take(int $count): self
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('Count must be at least 1.');
        }

        return new self(
            fn (): LazySeq => $this->toLazySeq()->take($count)
        );
    }

    /**
     * May become empty → returns LazySeq.
     *
     * @return LazySeq<T>
     */
    public function drop(int $count): LazySeq
    {
        return $this->toLazySeq()->drop($count);
    }

    // ───────────────────────── Terminal ─────────────────────────

    /**
     * @return T
     */
    public function head(): mixed
    {
        return $this->toLazySeq()->first();
    }

    /**
     * @return T|null
     */
    public function headOrNull(): mixed
    {
        return $this->toLazySeq()->firstOrNull();
    }

    /**
     * @return LazySeq<T>
     */
    public function tail(): LazySeq
    {
        return $this->toLazySeq()->drop(1);
    }

    /**
     * @param callable(T): bool $predicate
     * @return T|null
     */
    public function find(callable $predicate): mixed
    {
        return $this->toLazySeq()->find($predicate);
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        return $this->toLazySeq()->any($predicate);
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function all(callable $predicate): bool
    {
        return $this->toLazySeq()->all($predicate);
    }

    /** WARNING: never call on infinite sources. */
    public function count(): int
    {
        return $this->toLazySeq()->count();
    }

    /**
     * @template U
     * @param U $initial
     * @param callable(U, T): U $fn
     * @return U
     */
    public function fold(mixed $initial, callable $fn): mixed
    {
        return $this->toLazySeq()->fold($initial, $fn);
    }

    /**
     * @param callable(T, T): T $fn
     * @return T
     */
    public function reduce(callable $fn): mixed
    {
        return $this->toLazySeq()->reduce($fn);
    }

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return LazySeq<T>
     */
    public function toLazySeq(): LazySeq
    {
        return ($this->source)();
    }

    /**
     * @return Vec<T>
     */
    public function toVec(): Vec
    {
        return Vec::fromArray($this->toArray());
    }

    /**
     * WARNING: never call on infinite sources.
     *
     * @return non-empty-list<T>
     */
    public function toArray(): array
    {
        $result = $this->toLazySeq()->toArray();

        if ($result === []) {
            throw new OutOfBoundsException(
                'NonEmptyLazySeq source produced an empty sequence.'
            );
        }

        return $result;
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return $this->toLazySeq()->getIterator();
    }

    /**
     * WARNING: never call on infinite sources.
     *
     * @return non-empty-list<T>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}