# Query module design

`Exact\Query\Query` is an immutable, in-memory pipeline over the `iterable` contract. Collection classes do not depend on Query and do not need query-specific methods. The generic parameter represents the current pipeline item type; callbacks explicitly describe transformations and keys.

## Execution

`execute()` builds the plan as lazy stages and materializes its sequence output as `Vec`. `groupBy()` and `having()` retain keyed results as `Map`. `executeLazy()` returns `LazySeq` for sequence results and `Map` for group results.

Filter, projection, bounds, distinct, `unionAll()`, and most data transformations are generator-backed. Grouping and sorting are eager barriers. Hash joins materialize/index the right input; right/full joins also materialize the left input. `union()`, `intersect()`, and `except()` materialize the right input, while `whereIn()` builds a membership lookup from its candidate iterable. Cross joins buffer the right input and have O(n*m) output cost.

Callbacks are run in pipeline order and exceptions propagate. There is no callback reordering or purity inference. Multiple `orderBy()` stages that are directly adjacent are combined into a stable sort; each sort key selector is evaluated once per element. A query over a one-shot iterable should execute only once; replayability is the source's responsibility.

## Join semantics

Equi-join key selectors must return `int|string`; type-prefixed tokens prevent int/string key collisions. Matching duplicate keys produce every pair in deterministic input order. Outer joins call combiners with `Option` for absent sides. Default outputs are `Pair` values rather than unstructured arrays.

Expected hash-join complexity is O(n + m + r), with `r` output records. Cross join is O(n*m). Semi/anti joins preserve left-side order and duplicates while testing right-side membership.

## Grouping and aggregation

Executing a `groupBy()` pipeline yields an insertion-ordered `Map<key, Vec<items>>` and changes the pipeline item type to `Vec<T>`. Subsequent callbacks see group values until a transformation changes that type. Map-aware `where()` and `select()` preserve keys; sequence stages may convert the grouped map to a sequence. `having()` requires the current pipeline result to be a `Map` and receives its current value and key. `groupFold()` maintains one accumulator per key in a single input pass. `aggregateBy()` deliberately offers a group-collection callback where users need whole-group operations.

Scalar aggregates are explicit about empty behavior: `sum()` returns zero; `mean()`, `min()`, `max()`, `minBy()`, and `maxBy()` return `Option`. Numeric aggregation uses PHP int/float arithmetic; exact decimal precision is not promised.

## Query inspection

`explain()` returns `source`, `mode`, and `stages` (each with operation, detail, materialization flag, and broad complexity estimate) without iterating the source or evaluating callbacks. `profile()` is opt-in and executes once, returning the materialized result alongside source/stage counts, a materialization estimate, and elapsed nanoseconds.

## Benchmark

Run `php benchmarks/query-benchmark.php` from the repository root to compare a nested-loop join with a Query hash join over generated order and customer data. This is a local demonstration, not a performance guarantee; timings depend on the PHP runtime and machine.

## Scope decisions

- No SQL, persistence, ORM, reflection-based field selection, or implicit coercion.
- `selectFields()` is not provided; explicit typed callbacks are the projection API.
- Collection operations remain on collection types; Query composes them at the module boundary.
- `project()` and `pluck()` remain for the existing API; `map()` remains the general collection transformation.
