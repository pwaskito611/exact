<?php

declare(strict_types=1);

namespace Exact\Query;

use Exact\Collection\LazySeq;
use Exact\Collection\Map;
use Exact\Collection\Vec;
use Exact\Type\Option\Option;
use Exact\Type\Tuple\Pair;
use Generator;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use TypeError;

/**
 * Immutable in-memory query pipeline over any iterable collection.
 *
 * Streaming stages remain deferred in executeLazy(). Grouping and ordering
 * are materialization boundaries. A query over a one-shot iterable should
 * only be executed once.
 *
 * @template T
 */
final class Query
{
    /**
     * @param iterable<mixed> $source
     * @param list<array{type: string, args: array<string, mixed>}> $operations
     */
    private function __construct(
        private readonly iterable $source,
        private readonly array $operations = [],
    ) {
    }

    /**
     * Accepts Exact collections and any native iterable.
     *
     * @template U
     * @param iterable<U> $source
     * @return self<U>
     */
    public static function from(iterable $source): self
    {
        return new self($source);
    }

    /** @param callable(T): bool $predicate @return self<T> */
    public function where(callable $predicate): self
    {
        return $this->append('where', ['predicate' => $predicate]);
    }

    /** @param callable(T): bool $predicate @return self<T> */
    public function filter(callable $predicate): self
    {
        return $this->where($predicate);
    }

    /**
     * @template U
     * @param iterable<U> $values
     * @param null|callable(T): mixed $selector
     * @return self<T>
     */
    public function whereIn(iterable $values, ?callable $selector = null): self
    {
        return $this->append('whereIn', [
            'values' => $values,
            'selector' => $selector ?? static fn (mixed $value): mixed => $value,
            'negate' => false,
        ]);
    }

    /**
     * @template U
     * @param iterable<U> $values
     * @param null|callable(T): mixed $selector
     * @return self<T>
     */
    public function whereNotIn(iterable $values, ?callable $selector = null): self
    {
        return $this->append('whereIn', [
            'values' => $values,
            'selector' => $selector ?? static fn (mixed $value): mixed => $value,
            'negate' => true,
        ]);
    }

    /**
     * @template U
     * @param callable(T): U $projection
     * @return self<U>
     */
    public function select(callable $projection): self
    {
        return $this->append('select', ['projection' => $projection]);
    }

    /** @template U @param callable(T): U $projection @return self<U> */
    public function project(callable $projection): self
    {
        return $this->select($projection);
    }

    /** @template U @param callable(T): U $selector @return self<U> */
    public function pluck(callable $selector): self
    {
        return $this->select($selector);
    }

    /**
     * Equi-join operations preserve duplicates and left-to-right ordering.
     * Outer unmatched values are represented with Option.
     *
     * @template U
     * @template V
     * @param iterable<U> $other
     * @param callable(T): array-key $leftKey
     * @param callable(U): array-key $rightKey
     * @param null|callable(T, U): V $combine
     * @return self<V|Pair<T, U>>
     */
    public function innerJoin(iterable $other, callable $leftKey, callable $rightKey, ?callable $combine = null): self
    {
        return $this->joinOperation('inner', $other, $leftKey, $rightKey, $combine);
    }

    /**
     * @template U
     * @template V
     * @param iterable<U> $other
     * @param callable(T): array-key $leftKey
     * @param callable(U): array-key $rightKey
     * @param null|callable(T, Option<U>): V $combine
     * @return self<V|Pair<T, Option<U>>>
     */
    public function leftJoin(iterable $other, callable $leftKey, callable $rightKey, ?callable $combine = null): self
    {
        return $this->joinOperation('left', $other, $leftKey, $rightKey, $combine);
    }

    /**
     * @template U
     * @template V
     * @param iterable<U> $other
     * @param callable(T): array-key $leftKey
     * @param callable(U): array-key $rightKey
     * @param null|callable(Option<T>, U): V $combine
     * @return self<V|Pair<Option<T>, U>>
     */
    public function rightJoin(iterable $other, callable $leftKey, callable $rightKey, ?callable $combine = null): self
    {
        return $this->joinOperation('right', $other, $leftKey, $rightKey, $combine);
    }

    /**
     * @template U
     * @template V
     * @param iterable<U> $other
     * @param callable(T): array-key $leftKey
     * @param callable(U): array-key $rightKey
     * @param null|callable(Option<T>, Option<U>): V $combine
     * @return self<V|Pair<Option<T>, Option<U>>>
     */
    public function fullJoin(iterable $other, callable $leftKey, callable $rightKey, ?callable $combine = null): self
    {
        return $this->joinOperation('full', $other, $leftKey, $rightKey, $combine);
    }

    /**
     * Cross join output size is count(left) * count(right); use with care.
     *
     * @template U
     * @template V
     * @param iterable<U> $other
     * @param null|callable(T, U): V $combine
     * @return self<V|Pair<T, U>>
     */
    public function crossJoin(iterable $other, ?callable $combine = null): self
    {
        return $this->append('join', [
            'kind' => 'cross',
            'other' => $other,
            'combine' => $combine ?? static fn (mixed $left, mixed $right): Pair => Pair::of($left, $right),
        ]);
    }

    /**
     * @template U
     * @param iterable<U> $other
     * @param callable(T): array-key $leftKey
     * @param callable(U): array-key $rightKey
     * @return self<T>
     */
    public function semiJoin(iterable $other, callable $leftKey, callable $rightKey): self
    {
        return $this->joinOperation('semi', $other, $leftKey, $rightKey, null);
    }

    /**
     * @template U
     * @param iterable<U> $other
     * @param callable(T): array-key $leftKey
     * @param callable(U): array-key $rightKey
     * @return self<T>
     */
    public function antiJoin(iterable $other, callable $leftKey, callable $rightKey): self
    {
        return $this->joinOperation('anti', $other, $leftKey, $rightKey, null);
    }

    /**
     * Alias for innerJoin().
     *
     * @template U
     * @template V
     * @param iterable<U> $other
     * @param callable(T): array-key $leftKey
     * @param callable(U): array-key $rightKey
     * @param null|callable(T, U): V $combine
     * @return self<V|Pair<T, U>>
     */
    public function join(iterable $other, callable $leftKey, callable $rightKey, ?callable $combine = null): self
    {
        return $this->innerJoin($other, $leftKey, $rightKey, $combine);
    }

    /** @return self<T> */
    public function take(int $count): self
    {
        $this->assertNonNegative($count, 'take');
        return $this->append('take', ['count' => $count]);
    }

    /** @return self<T> */
    public function limit(int $count): self
    {
        return $this->take($count);
    }

    /** @return self<T> */
    public function drop(int $count): self
    {
        $this->assertNonNegative($count, 'drop');
        return $this->append('drop', ['count' => $count]);
    }

    /** @return self<T> */
    public function offset(int $count): self
    {
        return $this->drop($count);
    }

    /** @param callable(T): bool $predicate @return self<T> */
    public function takeWhile(callable $predicate): self
    {
        return $this->append('takeWhile', ['predicate' => $predicate]);
    }

    /** @param callable(T): bool $predicate @return self<T> */
    public function skipWhile(callable $predicate): self
    {
        return $this->append('skipWhile', ['predicate' => $predicate]);
    }

    /** @param null|callable(T): mixed $keySelector @return self<T> */
    public function distinct(?callable $keySelector = null): self
    {
        return $this->distinctBy($keySelector ?? static fn (mixed $value): mixed => $value);
    }

    /** @param callable(T): mixed $keySelector @return self<T> */
    public function distinctBy(callable $keySelector): self
    {
        return $this->append('distinct', ['keySelector' => $keySelector]);
    }

    /**
     * @template K of array-key
     * @param callable(T): K $keySelector
     * @return self<Vec<T>>
     */
    public function groupBy(callable $keySelector): self
    {
        return $this->append('groupBy', ['selector' => $keySelector]);
    }

    /**
     * Filter groups after groupBy(). The callback receives (group, key).
     *
     * @param callable(T, array-key): bool $predicate
     * @return self<T>
     */
    public function having(callable $predicate): self
    {
        return $this->append('having', ['predicate' => $predicate]);
    }

    /**
     * Single-pass per-key reduction without storing group members.
     *
     * @template K of array-key
     * @template A
     * @param callable(T): K $keySelector
     * @param A $initial
     * @param callable(A, T): A $reducer
     * @return Map<K, A>
     */
    public function groupFold(callable $keySelector, mixed $initial, callable $reducer): Map
    {
        $groups = [];
        foreach ($this->values() as $item) {
            $key = $keySelector($item);
            $this->assertMapKey($key);
            if (!array_key_exists($key, $groups)) {
                $groups[$key] = $initial;
            }
            $groups[$key] = $reducer($groups[$key], $item);
        }
        return Map::fromArray($groups);
    }

    /**
     * @template K of array-key
     * @template A
     * @param callable(T): K $keySelector
     * @param callable(Vec<T>, K): A $aggregate
     * @return Map<K, A>
     */
    public function aggregateBy(callable $keySelector, callable $aggregate): Map
    {
        $groups = $this->groupBy($keySelector)->execute();
        $result = [];
        foreach ($groups as $key => $items) {
            $result[$key] = $aggregate($items, $key);
        }
        return Map::fromArray($result);
    }

    /**
     * @template U
     * @param callable(T): U $selector
     * @param bool $descending
     * @param null|callable(U, U): int $comparator
     * @return self<T>
     */
    public function orderBy(callable $selector, bool $descending = false, ?callable $comparator = null): self
    {
        $criteria = [['selector' => $selector, 'descending' => $descending, 'comparator' => $comparator]];
        $operations = $this->operations;
        $last = array_key_last($operations);
        if ($last !== null && $operations[$last]['type'] === 'orderBy') {
            $operations[$last]['args']['criteria'][] = $criteria[0];
            return new self($this->source, $operations);
        }
        return $this->append('orderBy', ['criteria' => $criteria]);
    }

    /** @template U @param callable(T): U $selector @param bool $descending @param null|callable(U,U): int $comparator @return self<T> */
    public function thenBy(callable $selector, bool $descending = false, ?callable $comparator = null): self
    {
        return $this->orderBy($selector, $descending, $comparator);
    }

    /** Alias for orderBy(). @template U @param callable(T): U $selector @param bool $descending @return self<T> */
    public function sortBy(callable $selector, bool $descending = false): self
    {
        return $this->orderBy($selector, $descending);
    }

    /**
     * Set semantics deduplicate; order is first occurrence from left, then right.
     * Supplying equals without a key selector uses O(n*m) strict callback checks.
     *
     * @template U
     * @param iterable<U> $other
     * @param null|callable(T|U): mixed $keySelector
     * @param null|callable(T|U, T|U): bool $equals
     * @return self<T|U>
     */
    public function union(iterable $other, ?callable $keySelector = null, ?callable $equals = null): self
    {
        return $this->append('set', compact('other', 'keySelector', 'equals') + ['kind' => 'union']);
    }

    /** @template U @param iterable<U> $other @return self<T|U> */
    public function unionAll(iterable $other): self
    {
        return $this->append('set', ['kind' => 'unionAll', 'other' => $other]);
    }

    /** @template U @param iterable<U> $other @param null|callable(T|U): mixed $keySelector @param null|callable(T|U,T|U): bool $equals @return self<T> */
    public function intersect(iterable $other, ?callable $keySelector = null, ?callable $equals = null): self
    {
        return $this->append('set', compact('other', 'keySelector', 'equals') + ['kind' => 'intersect']);
    }

    /** @template U @param iterable<U> $other @param null|callable(T|U): mixed $keySelector @param null|callable(T|U,T|U): bool $equals @return self<T> */
    public function except(iterable $other, ?callable $keySelector = null, ?callable $equals = null): self
    {
        return $this->append('set', compact('other', 'keySelector', 'equals') + ['kind' => 'except']);
    }

    /** @template U @param U $initial @param callable(U,T): U $reducer @return U */
    public function aggregate(mixed $initial, callable $reducer): mixed
    {
        $accumulator = $initial;
        foreach ($this->values() as $item) {
            $accumulator = $reducer($accumulator, $item);
        }
        return $accumulator;
    }

    /** @template U @param U $initial @param callable(U,T): U $reducer @return U */
    public function fold(mixed $initial, callable $reducer): mixed
    {
        return $this->aggregate($initial, $reducer);
    }

    /**
     * Stops before reducing the first item for which $continue returns false.
     * @template U @param U $initial @param callable(U,T): bool $continue @param callable(U,T): U $reducer @return U
     */
    public function foldWhile(mixed $initial, callable $continue, callable $reducer): mixed
    {
        $accumulator = $initial;
        foreach ($this->values() as $item) {
            if (!$continue($accumulator, $item)) {
                break;
            }
            $accumulator = $reducer($accumulator, $item);
        }
        return $accumulator;
    }

    /** @template U @param U $initial @param callable(U,T): U $reducer @return self<U> */
    public function scan(mixed $initial, callable $reducer): self
    {
        return $this->append('scan', compact('initial', 'reducer'));
    }

    /**
     * @template U of int|float
     * @param null|callable(T): U $selector
     * @return int|float
     */
    public function sum(?callable $selector = null): int|float
    {
        $sum = 0;
        $selector ??= static fn (mixed $value): mixed => $value;
        foreach ($this->values() as $item) {
            $value = $selector($item);
            if (!is_int($value) && !is_float($value)) {
                throw new TypeError('Query::sum() selector must return int or float.');
            }
            $sum += $value;
        }
        return $sum;
    }

    /**
     * @template U of int|float
     * @param null|callable(T): U $selector
     * @return Option<float>
     */
    public function mean(?callable $selector = null): Option
    {
        $sum = 0;
        $count = 0;
        $selector ??= static fn (mixed $value): mixed => $value;
        foreach ($this->values() as $item) {
            $value = $selector($item);
            if (!is_int($value) && !is_float($value)) {
                throw new TypeError('Query::mean() selector must return int or float.');
            }
            $sum += $value;
            $count++;
        }
        return $count === 0 ? Option::none() : Option::some($sum / $count);
    }

    /** @return Option<float> */
    public function average(?callable $selector = null): Option
    {
        return $this->mean($selector);
    }

    /** @template U @param callable(T): U $selector @return Option<T> */
    public function minBy(callable $selector): Option
    {
        return $this->extremeBy($selector, false);
    }

    /** @template U @param callable(T): U $selector @return Option<T> */
    public function maxBy(callable $selector): Option
    {
        return $this->extremeBy($selector, true);
    }

    /** @return Option<T> */
    public function min(): Option
    {
        return $this->extremeBy(static fn (mixed $value): mixed => $value, false);
    }

    /** @return Option<T> */
    public function max(): Option
    {
        return $this->extremeBy(static fn (mixed $value): mixed => $value, true);
    }

    /** @template K of array-key @param callable(T): K $keySelector @return Map<K, int> */
    public function countBy(callable $keySelector): Map
    {
        return $this->groupFold($keySelector, 0, static fn (int $count, mixed $_): int => $count + 1);
    }

    /** @template K of array-key @param callable(T): K $keySelector @param callable(T): int|float $valueSelector @return Map<K, int|float> */
    public function sumBy(callable $keySelector, callable $valueSelector): Map
    {
        return $this->groupFold($keySelector, 0, static function (int|float $sum, mixed $item) use ($valueSelector): int|float {
            $value = $valueSelector($item);
            if (!is_int($value) && !is_float($value)) {
                throw new TypeError('Query::sumBy() selector must return int or float.');
            }
            return $sum + $value;
        });
    }

    /** @param callable(T): bool $predicate */
    public function exists(callable $predicate): bool
    {
        foreach ($this->values() as $item) {
            if ($predicate($item)) {
                return true;
            }
        }
        return false;
    }

    /** @return T @throws OutOfBoundsException */
    public function first(): mixed
    {
        foreach ($this->values() as $item) {
            return $item;
        }
        throw new OutOfBoundsException('Cannot get first item of an empty query.');
    }

    /** @return Option<T> */
    public function firstOrNone(): Option
    {
        foreach ($this->values() as $item) {
            return Option::some($item);
        }
        return Option::none();
    }

    /** @return T @throws OutOfBoundsException */
    public function last(): mixed
    {
        $found = false;
        $last = null;
        foreach ($this->values() as $item) {
            $found = true;
            $last = $item;
        }
        if (!$found) {
            throw new OutOfBoundsException('Cannot get last item of an empty query.');
        }
        return $last;
    }

    /** @return Option<T> */
    public function lastOrNone(): Option
    {
        $found = false;
        $last = null;
        foreach ($this->values() as $item) {
            $found = true;
            $last = $item;
        }
        return $found ? Option::some($last) : Option::none();
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->values() as $_) {
            $count++;
        }
        return $count;
    }

    /**
     * @template U
     * @param callable(T): bool $predicate
     * @return Pair<Vec<T>, Vec<T>>
     */
    public function partition(callable $predicate): Pair
    {
        $yes = [];
        $no = [];
        foreach ($this->values() as $item) {
            if ($predicate($item)) {
                $yes[] = $item;
            } else {
                $no[] = $item;
            }
        }
        return Pair::of(Vec::fromArray($yes), Vec::fromArray($no));
    }

    /** @param callable(T): mixed $effect @return self<T> */
    public function tap(callable $effect): self
    {
        return $this->append('tap', ['effect' => $effect]);
    }

    /** @template U @param iterable<U> $other @return self<Pair<T, U>> */
    public function zip(iterable $other): self
    {
        return $this->zipWith($other, static fn (mixed $left, mixed $right): Pair => Pair::of($left, $right));
    }

    /** @template U @template V @param iterable<U> $other @param callable(T,U): V $combine @return self<V> */
    public function zipWith(iterable $other, callable $combine): self
    {
        return $this->append('zip', ['other' => $other, 'combine' => $combine]);
    }

    /** @template U @param callable(T): iterable<U> $mapper @return self<U> */
    public function flatMap(callable $mapper): self
    {
        return $this->append('flatMap', ['mapper' => $mapper]);
    }

    /** @template U @param callable(T): Option<U>|U|null $mapper @return self<U> */
    public function filterMap(callable $mapper): self
    {
        return $this->append('filterMap', ['mapper' => $mapper]);
    }

    /**
     * Create overlapping fixed-size windows. Both size and step must be positive.
     * @return self<Vec<T>>
     */
    public function window(int $size, int $step = 1): self
    {
        if ($size <= 0 || $step <= 0) {
            throw new InvalidArgumentException('Query::window() size and step must be positive.');
        }
        return $this->append('window', compact('size', 'step'));
    }

    /**
     * Materialize the pipeline as Vec, or return Map for a grouped query.
     *
     * @return Vec<T>|Map<array-key, T>
     */
    public function execute(): Vec|Map
    {
        $result = $this->run();
        return $result instanceof Map ? $result : $result->toSeq();
    }

    /**
     * Keep streaming operations deferred. Grouping and sorting still materialize.
     *
     * @return LazySeq<T>|Map<array-key, T>
     */
    public function executeLazy(): LazySeq|Map
    {
        return $this->run();
    }

    /**
     * Return a readable execution plan without evaluating user callbacks.
     *
     * @return array{source: string, mode: string, stages: list<array{operation: string, detail: string, materializes: bool, complexity: string}>}
     */
    public function explain(bool $lazy = true): array
    {
        $stages = [];
        foreach ($this->operations as $operation) {
            $type = $operation['type'];
            $materializes = in_array($type, ['groupBy', 'having', 'orderBy', 'join'], true)
                || ($type === 'set' && $operation['args']['kind'] !== 'unionAll')
                || $type === 'whereIn';
            $complexity = match ($type) {
                'join' => $operation['args']['kind'] === 'cross' ? 'O(n*m)' : 'O(n+m+r)',
                'groupBy' => 'O(n)',
                'distinct' => 'O(n) average for scalar/object keys; array keys may be O(n^2)',
                'set' => ($operation['args']['equals'] ?? null) !== null ? 'O(n*m)' : 'O(n+m) average for scalar keys',
                'whereIn' => 'O(n+m) average',
                'orderBy' => 'O(n log n)',
                default => 'O(n)',
            };
            $detail = match ($type) {
                'join' => $operation['args']['kind'] . ' join',
                'set' => $operation['args']['kind'],
                'orderBy' => count($operation['args']['criteria']) . ' ordering criterion/criteria',
                'whereIn' => $operation['args']['negate'] ? 'not-in membership' : 'in membership',
                default => $type,
            };
            $stages[] = [
                'operation' => $type,
                'detail' => $detail,
                'materializes' => $materializes,
                'complexity' => $complexity,
            ];
        }
        return [
            'source' => get_debug_type($this->source),
            'mode' => $lazy ? 'lazy' : 'eager',
            'stages' => $stages,
        ];
    }

    /**
     * Execute once with opt-in counters. The result is included so profiling
     * does not require re-running a potentially one-shot source.
     *
     * @return array{result: Vec<T>|Map<array-key, T>, statistics: array{source_elements: int, output_elements: int, traversals: int, materializations: int, duration_ns: int, stages: list<array{operation: string, input: int|null, output: int}>}}
     */
    public function profile(): array
    {
        $startedAt = hrtime(true);
        $sourceCount = 0;
        $source = (function () use (&$sourceCount): Generator {
            foreach ($this->source as $item) {
                $sourceCount++;
                yield $item;
            }
        })();
        $counters = [];
        $result = $this->run($source, $counters);
        if ($result instanceof Map) {
            $outputCount = count($result);
            $materialized = $result;
        } else {
            $values = $result->toArray();
            $outputCount = count($values);
            $materialized = Vec::fromArray($values);
        }
        $stageStats = [];
        $previousCount = $sourceCount;
        foreach ($this->operations as $index => $operation) {
            $output = $counters[$index] ?? 0;
            $stageStats[] = [
                'operation' => $operation['type'],
                'input' => $previousCount,
                'output' => $output,
            ];
            $previousCount = $output;
        }
        return [
            'result' => $materialized,
            'statistics' => [
                'source_elements' => $sourceCount,
                'output_elements' => $outputCount,
                'traversals' => 1,
                'materializations' => count(array_filter(
                    $this->explain()['stages'],
                    static fn (array $stage): bool => $stage['materializes'],
                )) + 1,
                'duration_ns' => hrtime(true) - $startedAt,
                'stages' => $stageStats,
            ],
        ];
    }

    /**
     * @param iterable<mixed>|null $sourceOverride
     * @param array<int, int>|null $profileCounters
     * @return LazySeq<mixed>|Map<array-key, mixed>
     */
    private function run(?iterable $sourceOverride = null, ?array &$profileCounters = null): LazySeq|Map
    {
        $source = $sourceOverride ?? $this->source;
        $current = $source instanceof LazySeq ? $source : LazySeq::from($source);
        foreach ($this->operations as $index => $operation) {
            $type = $operation['type'];
            $args = $operation['args'];

            if ($type === 'groupBy') {
                $current = $this->asLazy($current)->groupBy($args['selector']);
            } elseif ($type === 'having') {
                if (!$current instanceof Map) {
                    throw new LogicException('Query::having() must follow groupBy().');
                }
                $current = $current->filter($args['predicate']);
            } elseif ($current instanceof Map) {
                $current = $this->applyToMap($current, $type, $args);
            } else {
                $current = $this->applyToSequence($this->asLazy($current), $type, $args);
            }

            if ($profileCounters !== null) {
                $profileCounters[$index] = $current instanceof Map ? count($current) : 0;
                if ($current instanceof LazySeq) {
                    $counter = &$profileCounters[$index];
                    $current = LazySeq::defer(static function () use ($current, &$counter): Generator {
                        foreach ($current as $item) {
                            $counter++;
                            yield $item;
                        }
                    });
                    unset($counter);
                }
            }
        }
        return $current;
    }

    /**
     * @param LazySeq<mixed> $values
     * @return LazySeq<mixed>
     */
    private function applyToSequence(LazySeq $values, string $type, array $args): LazySeq
    {
        return match ($type) {
            'where' => $values->filter($args['predicate']),
            'select' => $values->select($args['projection']),
            'take' => $values->take($args['count']),
            'drop' => $values->drop($args['count']),
            'takeWhile' => $values->takeWhile($args['predicate']),
            'skipWhile' => $values->dropWhile($args['predicate']),
            'whereIn' => $this->whereInSequence($values, $args),
            'distinct' => $this->distinctSequence($values, $args['keySelector']),
            'orderBy' => $this->ordered($values, $args['criteria']),
            'join' => $this->joinSequence($values, $args),
            'set' => $this->setSequence($values, $args),
            'scan' => $values->scan($args['initial'], $args['reducer']),
            'tap' => $values->map(static function (mixed $item) use ($args): mixed {
                ($args['effect'])($item);
                return $item;
            }),
            'flatMap' => $this->flatMapSequence($values, $args['mapper']),
            'filterMap' => $this->filterMapSequence($values, $args['mapper']),
            'zip' => $values->zipWith($this->asLazy($args['other']), $args['combine']),
            'window' => $this->windowSequence($values, $args['size'], $args['step']),
            default => throw new LogicException("Unsupported query operation: {$type}."),
        };
    }

    /** @return LazySeq<mixed>|Map<array-key, mixed> */
    private function applyToMap(Map $values, string $type, array $args): LazySeq|Map
    {
        return match ($type) {
            'where' => $values->filter(static fn (mixed $value, mixed $_key): bool => ($args['predicate'])($value)),
            'select' => $values->map(static fn (mixed $value, mixed $_key): mixed => ($args['projection'])($value)),
            'take' => Map::fromArray(array_slice($values->toArray(), 0, $args['count'], true)),
            'drop' => Map::fromArray(array_slice($values->toArray(), $args['count'], null, true)),
            'takeWhile', 'skipWhile' => $type === 'takeWhile'
                ? $this->asLazy($values)->takeWhile($args['predicate'])
                : $this->asLazy($values)->dropWhile($args['predicate']),
            'whereIn' => $this->whereInSequence($this->asLazy($values), $args),
            'distinct' => $this->distinctSequence($this->asLazy($values), $args['keySelector']),
            'orderBy' => $this->ordered($this->asLazy($values), $args['criteria']),
            'join' => $this->joinSequence($this->asLazy($values), $args),
            'set' => $this->setSequence($this->asLazy($values), $args),
            'scan' => $this->asLazy($values)->scan($args['initial'], $args['reducer']),
            'tap' => $this->asLazy($values)->map(static function (mixed $item) use ($args): mixed {
                ($args['effect'])($item);
                return $item;
            }),
            'flatMap' => $this->flatMapSequence($this->asLazy($values), $args['mapper']),
            'filterMap' => $this->filterMapSequence($this->asLazy($values), $args['mapper']),
            'zip' => $this->asLazy($values)->zipWith($this->asLazy($args['other']), $args['combine']),
            'window' => $this->windowSequence($this->asLazy($values), $args['size'], $args['step']),
            default => throw new LogicException("Unsupported query operation after grouping: {$type}."),
        };
    }

    /** @param iterable<mixed> $source @return LazySeq<mixed> */
    private function asLazy(iterable $source): LazySeq
    {
        return $source instanceof LazySeq ? $source : LazySeq::from($source);
    }

    /** @return LazySeq<mixed> */
    private function distinctSequence(LazySeq $source, callable $selector): LazySeq
    {
        return LazySeq::defer(static function () use ($source, $selector): Generator {
            $seen = [];
            $index = 0;
            foreach ($source as $item) {
                $key = $selector($item);
                $token = self::valueToken($key, $index++);
                if (self::containsStrict($seen[$token] ?? [], $key)) {
                    continue;
                }
                $seen[$token][] = $key;
                yield $item;
            }
        });
    }

    /** @param array<string, mixed> $args @return LazySeq<mixed> */
    private function whereInSequence(LazySeq $source, array $args): LazySeq
    {
        return LazySeq::defer(static function () use ($source, $args): Generator {
            $membership = [];
            $index = 0;
            foreach ($args['values'] as $value) {
                if (is_float($value) && is_nan($value)) {
                    $index++;
                    continue;
                }
                $membership[self::valueToken($value, $index++)][] = $value;
            }
            $selector = $args['selector'];
            foreach ($source as $item) {
                $key = $selector($item);
                $found = !(is_float($key) && is_nan($key))
                    && self::containsStrict($membership[self::valueToken($key, 0)] ?? [], $key);
                if ($found !== $args['negate']) {
                    yield $item;
                }
            }
        });
    }

    /** @return LazySeq<mixed> */
    private function ordered(LazySeq $source, array $criteria): LazySeq
    {
        return LazySeq::defer(static function () use ($source, $criteria): Generator {
            $items = $source->toArray();
            $decorated = [];
            foreach ($items as $index => $item) {
                $keys = [];
                foreach ($criteria as $criterion) {
                    $keys[] = ($criterion['selector'])($item);
                }
                $decorated[] = [$index, $item, $keys];
            }
            usort($decorated, static function (array $left, array $right) use ($criteria): int {
                foreach ($criteria as $index => $criterion) {
                    $comparison = isset($criterion['comparator'])
                        ? ($criterion['comparator'])($left[2][$index], $right[2][$index])
                        : ($left[2][$index] <=> $right[2][$index]);
                    if ($comparison !== 0) {
                        return $criterion['descending']
                            ? ($comparison > 0 ? -1 : 1)
                            : ($comparison <=> 0);
                    }
                }
                return $left[0] <=> $right[0];
            });
            foreach ($decorated as $entry) {
                yield $entry[1];
            }
        });
    }

    /** @return LazySeq<mixed> */
    private function joinSequence(LazySeq $left, array $args): LazySeq
    {
        return LazySeq::defer(static function () use ($left, $args): Generator {
            yield from self::joinItems(
                $left,
                $args['other'],
                $args['kind'],
                $args['leftKey'] ?? null,
                $args['rightKey'] ?? null,
                $args['combine'],
            );
        });
    }

    /**
     * @param iterable<mixed> $left
     * @param iterable<mixed> $right
     * @return Generator<int, mixed>
     */
    private static function joinItems(iterable $left, iterable $right, string $kind, ?callable $leftKey, ?callable $rightKey, callable $combine): Generator
    {
        $rightItems = [];
        $rightGroups = [];
        foreach ($right as $item) {
            $index = count($rightItems);
            $rightItems[] = $item;
            if ($kind !== 'cross' && $kind !== 'right') {
                $key = self::assertJoinKey($rightKey($item));
                $rightGroups[self::joinToken($key)][] = $index;
            }
        }

        if ($kind === 'cross') {
            foreach ($left as $leftItem) {
                foreach ($rightItems as $rightItem) {
                    yield $combine($leftItem, $rightItem);
                }
            }
            return;
        }

        if ($kind === 'right') {
            $leftGroups = [];
            foreach ($left as $leftItem) {
                $key = self::assertJoinKey($leftKey($leftItem));
                $leftGroups[self::joinToken($key)][] = $leftItem;
            }
            foreach ($rightItems as $rightItem) {
                $key = self::assertJoinKey($rightKey($rightItem));
                $matches = $leftGroups[self::joinToken($key)] ?? [];
                if ($matches === []) {
                    yield $combine(Option::none(), $rightItem);
                    continue;
                }
                foreach ($matches as $leftItem) {
                    yield $combine(Option::some($leftItem), $rightItem);
                }
            }
            return;
        }

        $matched = [];
        foreach ($left as $leftItem) {
            $key = self::assertJoinKey($leftKey($leftItem));
            $matches = $rightGroups[self::joinToken($key)] ?? [];
            if ($kind === 'semi') {
                if ($matches !== []) {
                    yield $leftItem;
                }
                continue;
            }
            if ($kind === 'anti') {
                if ($matches === []) {
                    yield $leftItem;
                }
                continue;
            }
            if ($matches === []) {
                if ($kind === 'left' || $kind === 'full') {
                    yield $kind === 'full'
                        ? $combine(Option::some($leftItem), Option::none())
                        : $combine($leftItem, Option::none());
                }
                continue;
            }
            foreach ($matches as $rightIndex) {
                $matched[$rightIndex] = true;
                yield $kind === 'full'
                    ? $combine(Option::some($leftItem), Option::some($rightItems[$rightIndex]))
                    : ($kind === 'left'
                        ? $combine($leftItem, Option::some($rightItems[$rightIndex]))
                        : $combine($leftItem, $rightItems[$rightIndex]));
            }
        }
        if ($kind === 'full') {
            foreach ($rightItems as $index => $rightItem) {
                if (!isset($matched[$index])) {
                    yield $combine(Option::none(), Option::some($rightItem));
                }
            }
        }
    }

    /** @return LazySeq<mixed> */
    private function setSequence(LazySeq $left, array $args): LazySeq
    {
        return LazySeq::defer(static function () use ($left, $args): Generator {
            $kind = $args['kind'];
            if ($kind === 'unionAll') {
                yield from $left;
                yield from $args['other'];
                return;
            }
            $right = iterator_to_array((static function () use ($args): Generator {
                yield from $args['other'];
            })(), false);
            $selector = $args['keySelector'] ?? static fn (mixed $value): mixed => $value;
            $equals = $args['equals'] ?? null;
            $rightKeys = [];
            $rightTokens = [];
            $rightSelectedKeys = [];
            foreach ($right as $index => $item) {
                $key = $selector($item);
                $rightKeys[] = $key;
                $rightSelectedKeys[] = $key;
                $rightTokens[self::valueToken($key, $index, 'right')][] = $key;
            }
            $emittedKeys = [];
            $seen = [];
            $leftIndex = count($right);
            foreach ($left as $item) {
                $key = $selector($item);
                $token = self::valueToken($key, $leftIndex++, 'left');
                $present = $equals !== null
                    ? self::matchesAny($key, $rightKeys, $equals)
                    : self::containsStrict($rightTokens[self::valueToken($key, 0, 'right')] ?? [], $key);
                if ($kind === 'intersect') {
                    if ($present && !self::alreadyEmitted($key, $token, $emittedKeys, $seen, $equals)) {
                        $emittedKeys[] = $key;
                        $seen[$token][] = $key;
                        yield $item;
                    }
                    continue;
                }
                if ($kind === 'except') {
                    if (!$present && !self::alreadyEmitted($key, $token, $emittedKeys, $seen, $equals)) {
                        $emittedKeys[] = $key;
                        $seen[$token][] = $key;
                        yield $item;
                    }
                    continue;
                }
                if (!self::alreadyEmitted($key, $token, $emittedKeys, $seen, $equals)) {
                    $seen[$token][] = $key;
                    $emittedKeys[] = $key;
                    yield $item;
                }
            }
            if ($kind === 'union') {
                foreach ($right as $index => $item) {
                    $key = $rightSelectedKeys[$index];
                    $token = self::valueToken($key, $index, 'right');
                    if (!self::alreadyEmitted($key, $token, $emittedKeys, $seen, $equals)) {
                        $seen[$token][] = $key;
                        $emittedKeys[] = $key;
                        yield $item;
                    }
                }
            }
        });
    }

    /** @return LazySeq<mixed> */
    private function flatMapSequence(LazySeq $source, callable $mapper): LazySeq
    {
        return LazySeq::defer(static function () use ($source, $mapper): Generator {
            foreach ($source as $item) {
                $inner = $mapper($item);
                if (!is_iterable($inner)) {
                    throw new TypeError('Query::flatMap() callback must return iterable.');
                }
                yield from $inner;
            }
        });
    }

    /** @return LazySeq<mixed> */
    private function filterMapSequence(LazySeq $source, callable $mapper): LazySeq
    {
        return LazySeq::defer(static function () use ($source, $mapper): Generator {
            foreach ($source as $item) {
                $mapped = $mapper($item);
                if ($mapped instanceof Option) {
                    if ($mapped->isSome()) {
                        yield $mapped->getOrNull();
                    }
                } elseif ($mapped !== null) {
                    yield $mapped;
                }
            }
        });
    }

    /** @return LazySeq<Vec<mixed>> */
    private function windowSequence(LazySeq $source, int $size, int $step): LazySeq
    {
        return LazySeq::defer(static function () use ($source, $size, $step): Generator {
            $buffer = [];
            $skip = 0;
            foreach ($source as $item) {
                if ($skip > 0) {
                    $skip--;
                    continue;
                }
                $buffer[] = $item;
                if (count($buffer) === $size) {
                    yield Vec::fromArray($buffer);
                    $buffer = array_slice($buffer, $step);
                    $skip = max(0, $step - $size);
                }
            }
        });
    }

    /** @template U @param callable(T): U $selector @return Option<T> */
    private function extremeBy(callable $selector, bool $maximum): Option
    {
        $found = false;
        $best = null;
        $bestKey = null;
        foreach ($this->values() as $item) {
            $key = $selector($item);
            if (!$found || ($maximum ? $key > $bestKey : $key < $bestKey)) {
                $found = true;
                $best = $item;
                $bestKey = $key;
            }
        }
        return $found ? Option::some($best) : Option::none();
    }

    /**
     * @param iterable<mixed> $source
     * @return iterable<mixed>
     */
    private function values(): iterable
    {
        $result = $this->run();
        return $result instanceof Map ? $result->values() : $result;
    }

    /** @param array<string, mixed> $args */
    private function joinOperation(string $kind, iterable $other, callable $leftKey, callable $rightKey, ?callable $combine): self
    {
        $default = match ($kind) {
            'left' => static fn (mixed $left, Option $right): Pair => Pair::of($left, $right),
            'right' => static fn (Option $left, mixed $right): Pair => Pair::of($left, $right),
            'full' => static fn (Option $left, Option $right): Pair => Pair::of($left, $right),
            'semi', 'anti' => static fn (mixed $left): mixed => $left,
            default => static fn (mixed $left, mixed $right): Pair => Pair::of($left, $right),
        };
        return $this->append('join', [
            'kind' => $kind,
            'other' => $other,
            'leftKey' => $leftKey,
            'rightKey' => $rightKey,
            'combine' => $combine ?? $default,
        ]);
    }

    /**
     * @template U
     * @param array<string, mixed> $args
     * @return self<U>
     */
    private function append(string $type, array $args): self
    {
        $operations = $this->operations;
        $operations[] = ['type' => $type, 'args' => $args];
        return new self($this->source, $operations);
    }

    private function assertNonNegative(int $value, string $method): void
    {
        if ($value < 0) {
            throw new InvalidArgumentException("Query::{$method}() count cannot be negative.");
        }
    }

    private function assertMapKey(mixed $key): void
    {
        if (!is_int($key) && !is_string($key)) {
            throw new InvalidArgumentException('Query grouping keys must be int or string.');
        }
    }

    private static function assertJoinKey(mixed $key): int|string
    {
        if (!is_int($key) && !is_string($key)) {
            throw new InvalidArgumentException('Query join key selectors must return int or string.');
        }
        return $key;
    }

    private static function joinToken(int|string $key): string
    {
        return is_int($key) ? 'i:' . $key : 's:' . strlen($key) . ':' . $key;
    }

    private static function valueToken(mixed $value, int $index, string $scope = ''): string
    {
        if (is_float($value) && is_nan($value)) {
            return 'nan:' . $scope . ':' . $index;
        }
        if (is_object($value)) {
            return 'object:' . spl_object_id($value);
        }
        if (is_resource($value)) {
            return 'resource:' . get_resource_id($value);
        }
        if (is_array($value)) {
            return 'array';
        }
        if (is_float($value) && $value === 0.0) {
            return 'float:zero';
        }
        return get_debug_type($value) . ':' . serialize($value);
    }

    private static function containsStrict(array $values, mixed $target): bool
    {
        foreach ($values as $value) {
            if ($value === $target) {
                return true;
            }
        }
        return false;
    }

    private static function matchesAny(mixed $value, array $candidates, callable $equals): bool
    {
        foreach ($candidates as $candidate) {
            if ($equals($value, $candidate)) {
                return true;
            }
        }
        return false;
    }

    private static function alreadyEmitted(mixed $key, string $token, array $keys, array $tokens, ?callable $equals): bool
    {
        if ($equals === null) {
            return self::containsStrict($tokens[$token] ?? [], $key);
        }
        foreach ($keys as $candidate) {
            if ($equals($key, $candidate)) {
                return true;
            }
        }
        return false;
    }
}
