# Query

`Exact\Query\Query` composes in-memory collection operations without adding query-specific methods to collection classes. `Query::from()` accepts any `iterable`, including `Vec`, `NonEmptyVec`, `LazySeq`, `NonEmptyLazySeq`, `Map`, and native arrays or generators.

```php
use Exact\Collection\Vec;
use Exact\Query\Query;

$orders = Vec::of(
    ['id' => 1, 'customerId' => 10, 'region' => 'west', 'total' => 80],
    ['id' => 2, 'customerId' => 11, 'region' => 'east', 'total' => 150],
);
$customers = Vec::of(
    ['id' => 10, 'name' => 'Ari'],
    ['id' => 11, 'name' => 'Bea'],
);

$report = Query::from($orders)
    ->innerJoin(
        $customers,
        static fn (array $order): int => $order['customerId'],
        static fn (array $customer): int => $customer['id'],
        static fn (array $order, array $customer): array => [
            'orderId' => $order['id'],
            'customer' => $customer['name'],
            'region' => $order['region'],
            'total' => $order['total'],
        ],
    )
    ->where(static fn (array $row): bool => $row['total'] >= 100)
    ->select(static fn (array $row): array => [
        'customer' => $row['customer'],
        'total' => $row['total'],
    ])
    ->execute();
```

`select()` accepts any projection callback result, including Exact tuples and domain-specific readonly objects, and its generic output type flows to later stages:

```php
final readonly class OrderReport
{
    public function __construct(
        public int $id,
        public string $customer,
        public float $total,
    ) {}
}

$reports = Query::from($orders)
    ->select(static fn (Order $order): OrderReport => new OrderReport(
        $order->id,
        $order->customerName,
        $order->total,
    ))
    ->execute();
```

`project()` is an alias for the same callback-based transformation; `pluck()` names the common case of extracting one value. Dynamic `selectFields()` is intentionally not provided.

## Filtering and terminals

Available pipeline filters and bounds:

- `where()` / `filter()`
- `whereIn($values, $selector = null)` / `whereNotIn(...)`
- `take()` / `limit()`, `drop()` / `offset()`
- `takeWhile()`, `skipWhile()`
- `distinct()` / `distinctBy()`

Terminals include `exists()`, `first()`, `firstOrNone()`, `last()`, `lastOrNone()`, `count()`, `aggregate()` / `fold()`, `foldWhile()`, `sum()`, `mean()` / `average()`, `min()` / `max()`, and `minBy()` / `maxBy()`. `firstOrNone()`, `lastOrNone()`, `mean()`, and extrema return `Option`; `first()` and `last()` throw `OutOfBoundsException` on empty input. `sum()` returns zero for empty input and rejects non-numeric selector results.

`countBy()`, `sumBy()`, `groupFold()`, and `aggregateBy()` provide grouped counts, sums, streaming reducers, or per-group aggregation. `groupBy()` returns a keyed `Map` when executed; `having()` filters those groups and receives `(group, key)`.

### Type changes after `groupBy()`

`groupBy()` changes the pipeline item type from `T` to `Vec<T>`. Until a transformation changes that type, subsequent callbacks receive a group `Vec<T>`, not an individual `T`; this includes `where()`, `select()`, `filterMap()`, `orderBy()`, and `exists()`. A later projection receives the current group and changes the item type for following callbacks. For example:

```php
$groupTotals = Query::from($orders)
    ->groupBy(static fn (Order $order): string => $order->region)
    ->select(static fn (Vec $group): float =>
        $group->foldLeft(0.0, static fn (float $sum, Order $order): float => $sum + $order->total)
    )
    ->execute(); // Map<region, float>; original group keys are retained
```

While the pipeline result is a grouped `Map`, `where()` and `select()` operate on its values and preserve group keys. Other stages may convert it back to a sequence, in which case those keys are no longer part of the output. `having()` requires the current pipeline result to be a `Map` (normally produced by `groupBy()`) and throws `LogicException` otherwise; it receives `(current map value, key)`, which is `(Vec<T>, key)` immediately after grouping. After grouping, `first()` / `last()` return a group `Vec<T>`, `count()` counts groups, and scalar numeric terminals such as `sum()` receive groups as their values (and will reject them as non-numeric unless a suitable selector is supplied). Use `groupFold()` or `aggregateBy()` when the goal is a per-key reduction rather than retaining group vectors.

## Joins

- `innerJoin()`, `leftJoin()`, `rightJoin()`, `fullJoin()`
- `semiJoin()`, `antiJoin()`
- `crossJoin()`

Equi-joins use int/string key selectors and hash lookup. Duplicate keys produce all matching pairs. The default result is an Exact `Pair`; outer joins wrap possibly absent sides in `Option`. A custom combiner can construct a domain object instead.

For finite input sizes `n` and `m`, hash joins are expected O(n + m + r), where `r` is the number of output matches. Cross join is O(n*m) in time and output size. The right input is indexed/materialized for hash joins; right/full joins also materialize the left side to preserve deterministic right-oriented output.

## Set and data transformations

`union()`, `intersect()`, and `except()` use set semantics: preserve first-seen order and remove duplicates. `unionAll()` concatenates and preserves duplicates. Default membership is type-aware and strict; objects compare by identity and arrays by strict array equality. Optional key selectors choose the values being compared; optional equality callbacks compare those selected values and may require linear comparisons. `distinct()` is hash-based for scalar/object values; array values use collision buckets and strict comparisons.

Other pipeline operations include `scan()`, `zip()` / `zipWith()`, `flatMap()`, `filterMap()`, `partition()`, `tap()`, and `window($size, $step = 1)`. `scan()` emits the initial accumulator first, followed by one accumulated value per input item. `zip()` and `zipWith()` stop when either input ends (the shorter input). `filterMap()` drops only `null` and `Option::none()`; it retains `false`, `0`, `''`, `[]`, and even `Option::some(null)`. `partition()` returns a `Pair<Vec<T>, Vec<T>>`; windows are full-size `Vec` values and omit a trailing partial window. When `$step > $size`, windows do not overlap and skip `$step - $size` source items between windows.

`orderBy()` supports ascending/descending direction and an optional comparator. Consecutive `orderBy()` / `thenBy()` calls form one stable multi-key sort. Selector results are computed once per item per criterion. `sortBy()` is the single-key convenience method without a custom comparator; use `orderBy()` when a comparator is needed.

Equality uses strict value comparisons: objects compare by identity, while arrays compare by strict array equality. `NaN` is a special case because it is not strictly equal to itself: `whereIn()` never matches a `NaN` candidate or source key, and `distinct()` / `distinctBy()` and default set-operation equality treat each `NaN` occurrence as unique rather than deduplicating it. A custom set-operation equality callback can define different behavior.

## Eager, lazy, and materialization

- `execute()` materializes sequence results as `Vec`; grouped results are `Map`.
- `executeLazy()` returns a deferred `LazySeq` for sequence pipelines.
- Filtering, projection, bounds, distinct, and most data transforms can stream.
- Grouping and sorting materialize. Hash joins materialize/index the right input; `rightJoin()` and `fullJoin()` also materialize/index the left input. `union()`, `intersect()`, and `except()` materialize the right input for membership checks; `unionAll()` instead streams the left input followed by the right input without deduplicating. `whereIn()` builds a membership lookup from its candidate iterable. Returning a `LazySeq` does not make these barriers streaming.
- Short-circuit terminals such as `exists()`, `first()`, and `take()` stop upstream iteration when no materialization barrier intervenes.

Like `LazySeq`, a query may be re-executed against a replayable source. A one-shot generator cannot be assumed replayable; execute it only once. Exceptions from source iterators and callbacks propagate.

## Inspection

```php
$plan = $query->explain();
// [
//     'source' => 'Exact\\Collection\\Vec',
//     'mode' => 'lazy',
//     'stages' => [
//         [
//             'operation' => 'where',
//             'detail' => 'where',
//             'materializes' => false,
//             'complexity' => 'O(n)',
//         ],
//     ],
// ]
$profile = $query->profile();
$rows = $profile['result']; // Vec or grouped Map
$statistics = $profile['statistics'];
```

`explain(bool $lazy = true)` returns exactly `source`, `mode`, and `stages`. Each stage contains `operation`, `detail`, `materializes`, and `complexity`; it does not evaluate the source or user callbacks, so it is safe to inspect a query backed by a one-shot generator.

`profile()` executes the query once and returns:

- `result`: the materialized `Vec` or grouped `Map`.
- `statistics`: `source_elements`, `output_elements`, `traversals`, `materializations`, `duration_ns`, and `stages`.
- `statistics.stages`: per-stage `operation`, `input`, and `output` counts.

`traversals` is `1`; `materializations` is an estimate based on planned barriers plus the final result. Joined/set/zip inputs are not included in `source_elements`. Since `profile()` consumes the query and includes its result, do not call it and then execute the same one-shot source again.

## Complete Public API

The following lists every public `Query` method. All pipeline methods return a new query; they do not add methods to `Vec`, `LazySeq`, or other collection classes.

| Area | Methods and signatures | Behavior |
|---|---|---|
| Creation | `Query::from(iterable $source): Query<T>` | Start from an Exact collection or any iterable; infer `T` from its items. |
| Filtering | `where(callable $predicate)`, `filter(callable $predicate)` | Keep matching items; `filter()` aliases `where()`. |
| Membership | `whereIn(iterable $values, ?callable $selector = null)`, `whereNotIn(iterable $values, ?callable $selector = null)` | Keep or exclude items whose selected value occurs in `$values`. The selector applies to source items; `$values` already contains comparable keys. |
| Projection | `select(callable $projection)`, `project(callable $projection)`, `pluck(callable $selector)` | Project to any output type; `project()` and `pluck()` use the same callback-based transformation semantics. `selectFields()` is intentionally not provided. |
| Equi-joins | `join(iterable $other, callable $leftKey, callable $rightKey, ?callable $combine = null)`, `innerJoin(iterable $other, callable $leftKey, callable $rightKey, ?callable $combine = null)`, `leftJoin(iterable $other, callable $leftKey, callable $rightKey, ?callable $combine = null)`, `rightJoin(iterable $other, callable $leftKey, callable $rightKey, ?callable $combine = null)`, `fullJoin(iterable $other, callable $leftKey, callable $rightKey, ?callable $combine = null)`, `semiJoin(iterable $other, callable $leftKey, callable $rightKey)`, `antiJoin(iterable $other, callable $leftKey, callable $rightKey)` | `join()` aliases `innerJoin()`. Default pair results use Exact `Pair`; outer unmatched values use `Option`. Semi/anti joins retain left items based on match presence. |
| Cross join | `crossJoin(iterable $other, ?callable $combine = null)` | Emit the cartesian product; default result is `Pair`. |
| Bounds | `take(int $count)`, `limit(int $count)`, `drop(int $count)`, `offset(int $count)`, `takeWhile(callable $predicate)`, `skipWhile(callable $predicate)` | Limit or skip items; `limit()` aliases `take()`, `offset()` aliases `drop()`. |
| Uniqueness | `distinct(?callable $keySelector = null)`, `distinctBy(callable $keySelector)` | Keep first occurrences; `distinctBy()` is the explicit selector form. |
| Grouping | `groupBy(callable $keySelector)`, `having(callable $predicate)` | `groupBy()` changes each pipeline item from `T` to `Vec<T>` and execution returns a `Map`; downstream callbacks receive each group. `having()` is valid only after `groupBy()`, filters groups, and receives `(group, key)`. |
| Group reducers | `groupFold(callable $keySelector, mixed $initial, callable $reducer): Map`, `aggregateBy(callable $keySelector, callable $aggregate): Map`, `countBy(callable $keySelector): Map`, `sumBy(callable $keySelector, callable $valueSelector): Map` | Produce per-key accumulations, aggregates, counts, or sums. Reducers receive `(accumulator, item)`; aggregate callbacks receive `(Vec<group items>, key)`. These execute eagerly. |
| Ordering | `orderBy(callable $selector, bool $descending = false, ?callable $comparator = null)`, `thenBy(callable $selector, bool $descending = false, ?callable $comparator = null)`, `sortBy(callable $selector, bool $descending = false)` | Build stable ordering criteria; `sortBy()` is the single-key convenience operation. |
| Set operations | `union(iterable $other, ?callable $keySelector = null, ?callable $equals = null)`, `unionAll(iterable $other)`, `intersect(iterable $other, ?callable $keySelector = null, ?callable $equals = null)`, `except(iterable $other, ?callable $keySelector = null, ?callable $equals = null)` | Set operations preserve first-seen ordering; `union()`, `intersect()`, and `except()` materialize the right input. `unionAll()` streams left then right without deduplicating. |
| Accumulation | `aggregate(mixed $initial, callable $reducer)`, `fold(mixed $initial, callable $reducer)`, `foldWhile(mixed $initial, callable $continue, callable $reducer)`, `scan(mixed $initial, callable $reducer)` | Reduce to one value, conditionally reduce, or project a running accumulator sequence. `fold()` aliases `aggregate()`. `scan()` emits the initial accumulator before processing source items. |
| Numeric terminals | `sum(?callable $selector = null): int|float`, `mean(?callable $selector = null): Option`, `average(?callable $selector = null): Option`, `min(): Option`, `max(): Option`, `minBy(callable $selector): Option`, `maxBy(callable $selector): Option` | Calculate numeric totals/means or select extrema; `average()` aliases `mean()`. Empty means and extrema return `Option::none()`. |
| Other terminals | `exists(callable $predicate): bool`, `first(): mixed`, `firstOrNone(): Option`, `last(): mixed`, `lastOrNone(): Option`, `count(): int` | Inspect a sequence; `exists()` short-circuits, and `first()` / `last()` throw on empty input. |
| Collection transforms | `partition(callable $predicate): Pair`, `tap(callable $effect)`, `zip(iterable $other)`, `zipWith(iterable $other, callable $combine)`, `flatMap(callable $mapper)`, `filterMap(callable $mapper)`, `window(int $size, int $step = 1)` | Partition to a pair of Vecs, run a side effect, zip only to the shorter input, flatten mapped iterables, drop only null/None results, or produce full-size windows. Windows with `step > size` leave gaps; size and step must be positive. |
| Execution | `execute(): Vec|Map`, `executeLazy(): LazySeq|Map` | Materialize results, or defer a stream-compatible sequence pipeline. Grouped results are Maps. |
| Inspection | `explain(bool $lazy = true): array`, `profile(): array` | Inspect planned stages/barriers or opt-in execution statistics. `profile()` executes the pipeline. |

Projection callbacks run once per item as their projection stage traverses its input. Projection is not pushed through filters, joins, grouping, or sorting: query order is preserved and projection is not evaluated early. Streaming-compatible projection remains lazy through `executeLazy()`; operations that require grouping, ordering, or join-side indexing retain their documented materialization behavior.
