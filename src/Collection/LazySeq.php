<?php

declare(strict_types=1);

namespace Exact\Collection;

use Exact\Type\Option\Option;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;
use Traversable;

/**
 * Lazy, pull-based sequence backed by generators.
 *
 * Each combinator returns a new LazySeq whose source is deferred until
 * iteration. Terminal operations (toArray, first, count, ...) re-execute
 * the source — wrap with memoize() if the source is expensive.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 */
final class LazySeq implements IteratorAggregate
{
    /** @var callable(): iterable<T> */
    private readonly mixed $source;

    /**
     * @param callable(): iterable<T> $source
     */
    private function __construct(callable $source)
    {
        $this->source = $source;
    }

    // ───────────────────────── Constructors ─────────────────────────

    /**
     * @template U
     * @param iterable<U> $source
     * @return self<U>
     */
    public static function from(iterable $source): self
    {
        return new self(static function () use ($source): Generator {
            yield from $source;
        });
    }

    /**
     * @template U
     * @param callable(): iterable<U> $source
     * @return self<U>
     */
    public static function defer(callable $source): self
    {
        return new self($source);
    }

    /**
     * @template U
     * @param U ...$values
     * @return self<U>
     */
    public static function of(mixed ...$values): self
    {
        return self::from($values);
    }

    /**
     * Infinite sequence: seed, next(seed), next(next(seed)), ...
     *
     * @template U
     * @param U $seed
     * @param callable(U): U $next
     * @return self<U>
     */
    public static function iterate(mixed $seed, callable $next): self
    {
        return new self(static function () use ($seed, $next): Generator {
            $current = $seed;

            while (true) {
                yield $current;
                $current = $next($current);
            }
        });
    }

    /**
     * Unfold: yield seed, then next(seed), until next returns null.
     *
     * @template U
     * @param U $seed
     * @param callable(U): U|null $next
     * @return self<U>
     */
    public static function unfold(mixed $seed, callable $next): self
    {
        return new self(static function () use ($seed, $next): Generator {
            $current = $seed;

            while ($current !== null) {
                yield $current;
                $current = $next($current);
            }
        });
    }

    /**
     * Generate until the callable returns null.
     *
     * @template U
     * @param callable(): U|null $generator
     * @return self<U>
     */
    public static function generate(callable $generator): self
    {
        return new self(static function () use ($generator): Generator {
            while (($value = $generator()) !== null) {
                yield $value;
            }
        });
    }

    /**
     * @template U
     * @param U $start
     * @param U $end
     * @param U $step
     * @return self<U>
     */
    public static function range(mixed $start, mixed $end, mixed $step = 1): self
    {
        return new self(static function () use ($start, $end, $step): Generator {
            if ($step == 0) {
                throw new InvalidArgumentException('Range step cannot be zero.');
            }

            for ($i = $start; $step > 0 ? $i < $end : $i > $end; $i += $step) {
                yield $i;
            }
        });
    }

    // ───────────────────────── Transformations ─────────────────────────

    /**
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        return new self(function () use ($fn): Generator {
            foreach ($this->iterateSource() as $item) {
                yield $fn($item);
            }
        });
    }

    /**
     * Query-oriented projection for selecting a derived record from each item.
     *
     * @template U
     * @param callable(T): U $projection
     * @return self<U>
     */
    public function select(callable $projection): self
    {
        return $this->map($projection);
    }

    /**
     * Alias for select().
     *
     * @template U
     * @param callable(T): U $projection
     * @return self<U>
     */
    public function project(callable $projection): self
    {
        return $this->select($projection);
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        return new self(function () use ($predicate): Generator {
            foreach ($this->iterateSource() as $item) {
                if ($predicate($item)) {
                    yield $item;
                }
            }
        });
    }

    /**
     * @template U
     * @param callable(T): Option<U>|U|null $fn
     * @return self<U>
     */
    public function filterMap(callable $fn): self
    {
        return new self(function () use ($fn): Generator {
            foreach ($this->iterateSource() as $item) {
                $mapped = $fn($item);

                if ($mapped instanceof Option) {
                    if ($mapped->isSome()) {
                        yield $mapped->getOrNull();
                    }

                    continue;
                }

                if ($mapped !== null) {
                    yield $mapped;
                }
            }
        });
    }

    /**
     * @template U
     * @param U $initial
     * @param callable(U, T): U $fn
     * @return self<U>
     */
    public function scan(mixed $initial, callable $fn): self
    {
        return new self(function () use ($initial, $fn): Generator {
            $accumulator = $initial;
            yield $accumulator;

            foreach ($this->iterateSource() as $item) {
                $accumulator = $fn($accumulator, $item);
                yield $accumulator;
            }
        });
    }

    /**
     * @return self<mixed>
     */
    public function flatten(): self
    {
        return new self(function (): Generator {
            foreach ($this->iterateSource() as $item) {
                if ($item instanceof Vec) {
                    foreach ($item->toArray() as $inner) {
                        yield $inner;
                    }

                    continue;
                }

                if (is_array($item)) {
                    foreach ($item as $inner) {
                        yield $inner;
                    }

                    continue;
                }

                if ($item instanceof Traversable) {
                    foreach ($item as $inner) {
                        yield $inner;
                    }

                    continue;
                }

                yield $item;
            }
        });
    }

    /**
     * @param callable(T): mixed $keySelector
     * @return self<T>
     */
    public function distinctBy(callable $keySelector): self
    {
        return new self(function () use ($keySelector): Generator {
            $seen = [];

            foreach ($this->iterateSource() as $item) {
                $key = $keySelector($item);
                $token = is_scalar($key) || $key === null
                    ? gettype($key) . ':' . (string) $key
                    : serialize($key);

                if (isset($seen[$token])) {
                    continue;
                }

                $seen[$token] = true;
                yield $item;
            }
        });
    }

    /**
     * @template K of array-key
     * @param callable(T): K $keySelector
     * @return Map<K, Vec<T>>
     */
    public function groupBy(callable $keySelector): Map
    {
        $groups = [];

        foreach ($this->iterateSource() as $item) {
            $key = $keySelector($item);

            if (!is_int($key) && !is_string($key)) {
                throw new InvalidArgumentException(
                    'LazySeq::groupBy() keys must be int or string.'
                );
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }

            $groups[$key][] = $item;
        }

        $result = [];

        foreach ($groups as $key => $items) {
            $result[$key] = Vec::fromArray($items);
        }

        return Map::fromArray($result);
    }

    /**
     * @template U
     * @param callable(T): U $selector
     * @return self<T>
     */
    public function sortBy(callable $selector): self
    {
        return self::from($this->toSeq()->sortBy($selector)->toArray());
    }

    /**
     * @template U
     * @param callable(T): U $selector
     * @return T|null
     */
    public function minBy(callable $selector): mixed
    {
        $best = null;
        $bestValue = null;
        $hasValue = false;

        foreach ($this->iterateSource() as $item) {
            $value = $selector($item);

            if (!$hasValue || $value < $bestValue) {
                $best = $item;
                $bestValue = $value;
                $hasValue = true;
            }
        }

        return $best;
    }

    /**
     * @template U
     * @param callable(T): U $selector
     * @return T|null
     */
    public function maxBy(callable $selector): mixed
    {
        $best = null;
        $bestValue = null;
        $hasValue = false;

        foreach ($this->iterateSource() as $item) {
            $value = $selector($item);

            if (!$hasValue || $value > $bestValue) {
                $best = $item;
                $bestValue = $value;
                $hasValue = true;
            }
        }

        return $best;
    }

    /**
     * @template K of array-key
     * @template U
     * @template V
     * @param self<U> $other
     * @param callable(T): K $leftKeySelector
     * @param callable(U): K $rightKeySelector
     * @param callable(T, U): V $combine
     * @return self<V>
     */
    public function innerJoin(self $other, callable $leftKeySelector, callable $rightKeySelector, callable $combine): self
    {
        return new self(function () use ($other, $leftKeySelector, $rightKeySelector, $combine): Generator {
            $rightGroups = [];

            foreach ($other->toArray() as $rightItem) {
                $key = $rightKeySelector($rightItem);
                $token = self::joinToken($key);
                $rightGroups[$token][] = $rightItem;
            }

            foreach ($this->iterateSource() as $leftItem) {
                $key = $leftKeySelector($leftItem);
                $token = self::joinToken($key);

                foreach ($rightGroups[$token] ?? [] as $rightItem) {
                    yield $combine($leftItem, $rightItem);
                }
            }
        });
    }

    /**
     * @template K of array-key
     * @template U
     * @template V
     * @param self<U> $other
     * @param callable(T): K $leftKeySelector
     * @param callable(U): K $rightKeySelector
     * @param callable(T, Option<U>): V $combine
     * @return self<V>
     */
    public function leftJoin(self $other, callable $leftKeySelector, callable $rightKeySelector, callable $combine): self
    {
        return new self(function () use ($other, $leftKeySelector, $rightKeySelector, $combine): Generator {
            $rightGroups = [];

            foreach ($other->toArray() as $rightItem) {
                $key = $rightKeySelector($rightItem);
                $token = self::joinToken($key);
                $rightGroups[$token][] = $rightItem;
            }

            foreach ($this->iterateSource() as $leftItem) {
                $token = self::joinToken($leftKeySelector($leftItem));
                $matches = $rightGroups[$token] ?? [];

                if ($matches === []) {
                    yield $combine($leftItem, Option::none());
                    continue;
                }

                foreach ($matches as $rightItem) {
                    yield $combine($leftItem, Option::some($rightItem));
                }
            }
        });
    }

    /**
     * @template K of array-key
     * @template U
     * @template V
     * @param self<U> $other
     * @param callable(T): K $leftKeySelector
     * @param callable(U): K $rightKeySelector
     * @param callable(Option<T>, U): V $combine
     * @return self<V>
     */
    public function rightJoin(self $other, callable $leftKeySelector, callable $rightKeySelector, callable $combine): self
    {
        return new self(function () use ($other, $leftKeySelector, $rightKeySelector, $combine): Generator {
            $leftGroups = [];

            foreach ($this->iterateSource() as $leftItem) {
                $key = $leftKeySelector($leftItem);
                $token = self::joinToken($key);
                $leftGroups[$token][] = $leftItem;
            }

            foreach ($other->toArray() as $rightItem) {
                $token = self::joinToken($rightKeySelector($rightItem));
                $matches = $leftGroups[$token] ?? [];

                if ($matches === []) {
                    yield $combine(Option::none(), $rightItem);
                    continue;
                }

                foreach ($matches as $leftItem) {
                    yield $combine(Option::some($leftItem), $rightItem);
                }
            }
        });
    }

    /**
     * @template K of array-key
     * @template U
     * @template V
     * @param self<U> $other
     * @param callable(T): K $leftKeySelector
     * @param callable(U): K $rightKeySelector
     * @param callable(Option<T>, Option<U>): V $combine
     * @return self<V>
     */
    public function fullJoin(self $other, callable $leftKeySelector, callable $rightKeySelector, callable $combine): self
    {
        return new self(function () use ($other, $leftKeySelector, $rightKeySelector, $combine): Generator {
            $rightValues = $other->toArray();
            $leftItems = $this->toArray();
            $rightGroups = [];
            $leftGroups = [];

            foreach ($rightValues as $rightItem) {
                $key = $rightKeySelector($rightItem);
                $token = self::joinToken($key);
                $rightGroups[$token][] = $rightItem;
            }

            foreach ($leftItems as $leftItem) {
                $key = $leftKeySelector($leftItem);
                $token = self::joinToken($key);
                $leftGroups[$token][] = $leftItem;
            }

            $matchedTokens = [];

            foreach ($leftItems as $leftItem) {
                $token = self::joinToken($leftKeySelector($leftItem));
                $matches = $rightGroups[$token] ?? [];

                if ($matches === []) {
                    yield $combine(Option::some($leftItem), Option::none());
                    continue;
                }

                $matchedTokens[$token] = true;

                foreach ($matches as $rightItem) {
                    yield $combine(Option::some($leftItem), Option::some($rightItem));
                }
            }

            foreach ($rightValues as $rightItem) {
                $token = self::joinToken($rightKeySelector($rightItem));

                if (isset($matchedTokens[$token]) || isset($leftGroups[$token])) {
                    continue;
                }

                yield $combine(Option::none(), Option::some($rightItem));
            }
        });
    }

    /**
     * @template U
     * @template V
     * @param self<U> $other
     * @param callable(T, U): V $combine
     * @return self<V>
     */
    public function crossJoin(self $other, callable $combine): self
    {
        return new self(function () use ($other, $combine): Generator {
            $rightItems = $other->toArray();

            foreach ($this->iterateSource() as $leftItem) {
                foreach ($rightItems as $rightItem) {
                    yield $combine($leftItem, $rightItem);
                }
            }
        });
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function reject(callable $predicate): self
    {
        return $this->filter(
            static fn (mixed $value): bool => !$predicate($value)
        );
    }

    /**
     * @template U
     * @param callable(T): iterable<U> $fn
     * @return self<U>
     */
    public function flatMap(callable $fn): self
    {
        return new self(function () use ($fn): Generator {
            foreach ($this->iterateSource() as $item) {
                yield from $fn($item);
            }
        });
    }

    /**
     * @param callable(T): mixed $fn
     * @return self<T>
     */
    public function tap(callable $fn): self
    {
        return new self(function () use ($fn): Generator {
            foreach ($this->iterateSource() as $item) {
                $fn($item);
                yield $item;
            }
        });
    }

    // ───────────────────────── Slicing ─────────────────────────

    /**
     * @return self<T>
     */
    public function take(int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Count cannot be negative.');
        }

        return new self(function () use ($count): Generator {
            if ($count === 0) {
                return;
            }

            $taken = 0;

            foreach ($this->iterateSource() as $item) {
                yield $item;

                if (++$taken >= $count) {
                    break;
                }
            }
        });
    }

    /**
     * @return self<T>
     */
    public function drop(int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Count cannot be negative.');
        }

        return new self(function () use ($count): Generator {
            $skipped = 0;

            foreach ($this->iterateSource() as $item) {
                if ($skipped < $count) {
                    $skipped++;
                    continue;
                }

                yield $item;
            }
        });
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function takeWhile(callable $predicate): self
    {
        return new self(function () use ($predicate): Generator {
            foreach ($this->iterateSource() as $item) {
                if (!$predicate($item)) {
                    return;
                }
                yield $item;
            }
        });
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function dropWhile(callable $predicate): self
    {
        return new self(function () use ($predicate): Generator {
            $dropping = true;

            foreach ($this->iterateSource() as $item) {
                if ($dropping && $predicate($item)) {
                    continue;
                }

                $dropping = false;
                yield $item;
            }
        });
    }

    /**
     * @return self<T>
     */
    public function distinct(): self
    {
        return new self(function (): Generator {
            $seen = [];

            foreach ($this->iterateSource() as $item) {
                $key = is_scalar($item) || $item === null
                    ? gettype($item) . ':' . (string) $item
                    : serialize($item);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                yield $item;
            }
        });
    }

    /**
     * @return self<array{0: T, 1: T}>
     */
    public function pairwise(): self
    {
        return new self(function (): Generator {
            $previous = null;
            $isFirst = true;

            foreach ($this->iterateSource() as $item) {
                if (!$isFirst) {
                    yield [$previous, $item];
                }

                $isFirst = false;
                $previous = $item;
            }
        });
    }

    /**
     * @return self<list<T>>
     */
    public function chunk(int $size): self
    {
        if ($size < 1) {
            throw new InvalidArgumentException('Chunk size must be at least 1.');
        }

        return new self(function () use ($size): Generator {
            $buffer = [];

            foreach ($this->iterateSource() as $item) {
                $buffer[] = $item;

                if (count($buffer) === $size) {
                    yield $buffer;
                    $buffer = [];
                }
            }

            if ($buffer !== []) {
                yield $buffer;
            }
        });
    }

    // ───────────────────────── Combining ─────────────────────────

    /**
     * @param self<T> $other
     * @return self<T>
     */
    public function concat(self $other): self
    {
        return new self(function () use ($other): Generator {
            yield from $this->iterateSource();
            yield from $other->iterate();
        });
    }

    /**
     * @template U
     * @param self<U> $other
     * @return self<array{0: T, 1: U}>
     */
    public function zip(self $other): self
    {
        return $this->zipWith(
            $other,
            fn (mixed $a, mixed $b): array => [$a, $b],
        );
    }

    /**
     * @template U
     * @template V
     * @param self<U> $other
     * @param callable(T, U): V $fn
     * @return self<V>
     */
    public function zipWith(self $other, callable $fn): self
    {
        return new self(function () use ($other, $fn): Generator {
            $left = $this->iterateSource();
            $right = $other->iterateSource();

            $left->rewind();
            $right->rewind();

            while ($left->valid() && $right->valid()) {
                yield $fn($left->current(), $right->current());
                $left->next();
                $right->next();
            }
        });
    }

    /**
     * @return self<array{0: int, 1: T}>
     */
    public function enumerate(): self
    {
        return new self(function (): Generator {
            $index = 0;

            foreach ($this->iterateSource() as $item) {
                yield [$index++, $item];
            }
        });
    }

    // ───────────────────────── Terminal operations (eager) ─────────────────────────

    /**
     * @return T
     * @throws OutOfBoundsException
     */
    public function first(): mixed
    {
        foreach ($this->iterateSource() as $item) {
            return $item;
        }

        throw new OutOfBoundsException(
            'Cannot get first of an empty LazySeq.'
        );
    }

    /**
     * @return T|null
     */
    public function firstOrNull(): mixed
    {
        foreach ($this->iterateSource() as $item) {
            return $item;
        }

        return null;
    }

    /**
     * @param callable(T): bool $predicate
     * @return T|null
     */
    public function find(callable $predicate): mixed
    {
        foreach ($this->iterateSource() as $item) {
            if ($predicate($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        foreach ($this->iterateSource() as $item) {
            if ($predicate($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function all(callable $predicate): bool
    {
        foreach ($this->iterateSource() as $item) {
            if (!$predicate($item)) {
                return false;
            }
        }

        return true;
    }

    public function count(): int
    {
        $count = 0;

        foreach ($this->iterateSource() as $_) {
            $count++;
        }

        return $count;
    }

    /**
     * @template U
     * @param U $initial
     * @param callable(U, T): U $fn
     * @return U
     */
    public function fold(mixed $initial, callable $fn): mixed
    {
        $accumulator = $initial;

        foreach ($this->iterateSource() as $item) {
            $accumulator = $fn($accumulator, $item);
        }

        return $accumulator;
    }

    /**
     * @param callable(T, T): T $fn
     * @return T
     * @throws OutOfBoundsException
     */
    public function reduce(callable $fn): mixed
    {
        $hasValue = false;
        $accumulator = null;

        foreach ($this->iterateSource() as $item) {
            if (!$hasValue) {
                $accumulator = $item;
                $hasValue = true;
            } else {
                $accumulator = $fn($accumulator, $item);
            }
        }

        if (!$hasValue) {
            throw new OutOfBoundsException('Cannot reduce an empty LazySeq.');
        }

        return $accumulator;
    }

    // ───────────────────────── Materialization ─────────────────────────

    /**
     * Force into a Vec. Runs the source once.
     *
     * @return Vec<T>
     */
    public function toSeq(): Vec
    {
        return Vec::fromArray(
            iterator_to_array($this->iterateSource(), false)
        );
    }

    /**
     * Force into an array. Runs the source once.
     *
     * @return list<T>
     */
    public function toArray(): array
    {
        return iterator_to_array($this->iterateSource(), false);
    }

    /**
     * Cache the result so subsequent iteration does not re-execute source.
     *
     * @return self<T>
     */
    public function memoize(): self
    {
        $cache = null;

        return new self(function () use (&$cache): Generator {
            if ($cache === null) {
                $cache = iterator_to_array($this->iterateSource(), false);
            }

            yield from $cache;
        });
    }

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return $this->iterateSource();
    }

    /**
     * @return string
     */
    private static function joinToken(mixed $value): string
    {
        if (is_int($value) || is_string($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return 'bool:' . ($value ? '1' : '0');
        }

        if ($value === null) {
            return 'null';
        }

        return gettype($value) . ':' . serialize($value);
    }

    // ───────────────────────── Internal ─────────────────────────

    /**
     * @return Generator<int, T>
     */
    private function iterateSource(): Generator
    {
        $source = ($this->source)();

        yield from $source;
    }
}