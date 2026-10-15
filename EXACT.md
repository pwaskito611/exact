# EXACT.md — agent guide for `pwaskito611/exact` (PHP)

Purpose: use the library correctly with minimal tokens. Read this first. Do NOT read README or full docs unless a method is missing here.

## Facts
- PHP >= 8.2, no runtime deps. Root namespace `Exact\`. Tests: `composer test` (PHPUnit).
- Install: README says `composer require pandu/exact` (repo is `pwaskito611/exact`). Check `composer.json` "name" before running install.
- Everything is **immutable**. Methods return new instances. Ignoring the return value = bug (`$v->append(1);` does nothing).
- Constructors are private/limited: use static factories (`Vec::of`, `Either::right`, `Map::of`, ...). Exception: `Some`, `None`, `Matcher`, `Variant`-implementing classes have public constructors, but prefer the abstraction.
- Do not invent methods. If a method is not listed here, grep `src/` or fetch the one relevant doc page (see "Lookup").

## Imports
```php
use Exact\Type\Option\{Option, Some, None};
use Exact\Type\Either\Either;          // Left/Right classes: same namespace (verify)
use Exact\Type\Validated\Validated;
use Exact\Pattern\{PatternMatch, Matcher, Variant, Adt};
use Exact\Collection\{Vec, Map};       // other collections: Exact\Collection\<Name>
```
Exceptions live under `Exact\Exception\` (verify exact names via grep).

## Which type?
| Need | Use |
|---|---|
| Value may be absent, no error detail | `Option` |
| Two meaningful alternatives / typed error, fail-fast | `Either` (right-biased; Left meaning is yours) |
| Several independent input checks, collect all errors | `Validated` |
| Named domain states + dispatch | `Variant` + `PatternMatch` (or class implementing `Adt`) |
| Ordered list / keyed / unique / lazy | `Vec` / `Map` / `Set` / `LazySeq` |

## Option
- Create: `Option::some($v)` (always Some, even null), `none()`, `from($v)` (None only for `null`; `''`,`0`,`[]` are Some), `when($cond,$v)`, `tryCatch(fn)` (Throwable → None).
- Transform: `map`, `flatMap` (callback returns Option), `filter`, `tap`, `orElse(Option)`, `orElseGet`.
- Extract: `getOrElse($raw)` (eager), `getOrElseGet(fn)`, `getOrElseThrow(fn(): Throwable)` ← callable, `getOrNull()`, `fold(onNone, onSome)`.
- Check: `isSome()`, `isNone()`, `exists`, `forall`, `contains`.
- Combine: `zip`, `zipWith($o,fn)`, `ap`, `flatten`; statics `firstSome([])`, `sequence([])`/`traverse($items,fn)` (None if any None), `collect([])` (drops None, returns plain list).
- Convert: `toEither($left)`, `toArray()`.
- Gotchas: `Some::value()` only on concrete `Some`; `None::value()` throws `LogicException`. `getOrNull()` can't distinguish `Some(null)` from `None` → use `isSome()`.

## Either
- Create: `Either::left($x)`, `right($x)`, `of`/`pure` (= right), `fromNullable($v,$left)`, `cond($bool,$right,$left)`, `tryCatch(fn, ?errFn)` (no errFn → Throwable stored as Left).
- `map` (Right only), `mapLeft`, `bimap(fnL,fnR)`, `flatMap` (must return Either else `TypeError`), `flatMapLeft` (recover), `filter($pred,$leftIfFail)`.
- Extract: `fold(onLeft, onRight)` (prefer this), `getOrElse($raw)`, `getOrElseGet(fn)`, `getOrElseThrow(Throwable $instance)` ← instance, NOT callable (differs from Option), `getOrNull()`, `getLeftOrNull()`.
- `getLeft()`/`getRight()` throw `LogicException` on wrong side. `get()`/`unsafeGet()` are unchecked.
- Also: `orElse`, `orElseGet`, `tap`, `tapLeft`, `exists`, `forall`, `contains`, `zip`, `zipWith`, `ap`, `flatten`, `swap`, `toOption`; statics `sequence`/`traverse` stop at first Left.

## Validated
- Create: `Validated::valid($v)`, `invalid($e)`, `of`/`pure` (= valid), `cond($bool,$v,$err)`, `tryCatch(fn, errFn)` (errFn **required**).
- `map`, `mapInvalid`, `bimap`, `andThen` (fail-fast, does NOT accumulate), `filter($pred,$err)`, `orElse`, `orElseGet`, `tap`, `tapInvalid`, `fold(onInvalid, onValid)`.
- Accumulation is **manual**: `combine`/`zip($other, $errCombiner)`, `zipWith($other, $fn, $errCombiner)`, `ap($other,$errCombiner)`. Combiner `(e1,e2)` runs only when both invalid.
- Aggregate: `sequence($list,$errListFn)`, `traverse($items,$fn,$errListFn)`, `partition($list)` → `[$errors, $values]`, `firstValid($list,$errListFn)`.
- Extract: `getOrElse`, `getOrElseGet`, `getOrElseThrow`, `getOrNull`, `getInvalidOrNull`, `getValid`/`getInvalid` (throw `LogicException` on wrong branch), `unsafeGet`. There is **no `get()`**.
- Convert: `toEither`, `toOption`, `swap`. JSON: `['valid'=>true,'value'=>..]` / `['valid'=>false,'errors'=>..]`.

## Variant + PatternMatch
```php
$r = PatternMatch::on(Variant::of('Paid', 'R-1'))        // Variant::unit('X') = null payload
    ->case('Paid', fn (Variant $v) => $v->value())       // handler gets the SUBJECT, not the payload
    ->case(['Pending','Processing'], fn (Variant $v) => 'wait')
    ->caseWhen('Failed', fn (Variant $v): bool => $cond, fn (Variant $v) => 'x') // guard may be null
    ->default(fn (Variant $v) => $v->name())             // alias: otherwise()
    ->run();
```
- `Variant`: `of($name,$value=null)`, `unit($name)`, `name()`, `value()`, `is($name)`, `equals`.
- Key matching: subject implements `Adt` (Variant does) → `matchKey()` strict-equals key. Otherwise key must be a class/interface name (`instanceof`). `Some/None/Left/Right` are NOT `Adt` → match with `Some::class`, `None::class`, etc.
- Same key registered twice → handlers appended, tried in order (first passing guard wins). `default()` twice → replaced.
- `PatternMatch::on($subject, exhaustive: ['A','B'])` or `->exhaustive([...])`: `run()` throws `LogicException` if a listed key has no handler; a registered default skips this check.
- Run: `run()` (throws `MatchException` if no match & no default), `runOrNull()`, `runOrElse($v)`, `runOrElseGet(fn)`. After any run, adding cases/default throws `LogicException` → build a new matcher each time.

## Vec (immutable ordered list, may be empty)
- Create: `Vec::empty()`, `of(...$items)`, `singleton($v)`, `fromArray($a)` (drops keys), `fromIterable($it)`. `Seq` = alias of `Vec`.
- Read: `first/head`, `last`, `tail`, `at($i)/get($i)`, `firstOrNull`, `lastOrNull`, `atOrNull($i)`, `count/size`, `isEmpty/isNotEmpty`.
  - `first/last/tail` on empty → `EmptyCollectionException`; `at/get` out of range → `OutOfBoundsException`; `reduce` on empty → `OutOfBoundsException`.
- Transform: `map(fn($x))`, `filter`, `reject`, `flatMap` (callback must return `Vec`, else `TypeError`), `tap`/`each` (return same instance, NOT void), `unique` (structural), `reverse`, `zip`, `zipWith($o,fn)`, `enumerate` (→ `[i,x]` pairs).
- Slice: `take($n)`, `drop($n)` (negative → `InvalidArgumentException`), `slice($offset,?$len)`, `takeWhile`, `dropWhile`.
- Combine: `append`, `prepend`, `concat(Vec)`.
- Reduce: `foldLeft($init, fn($acc,$x))`, `foldRight($init, fn($x,$acc))` ← arg order flips, `reduce(fn($acc,$x))`.
- Test: `contains($v)` strict `===`, `exists(fn)`, `forall(fn)`, `equals(Vec)` (order matters).
- Out: `toArray()` (list), iterable, `json_encode` → JSON array.

## Map (immutable, string|int keys, insertion order)
- Create: `Map::empty()`, `of($k1,$v1,$k2,$v2,...)` (odd args → `InvalidArgumentException`), `singleton($k,$v)`, `fromArray`, `fromIterable`.
- Lookup: `has`, `missing`, `get($k)` (throws `OutOfBoundsException`), `getOrNull`, `getOrElse($k,$default)`, `getOrElseGet($k,fn)`. All use `array_key_exists` (stored `null` counts as present).
- Update: `put($k,$v)`, `remove($k)`, `merge(Map)` (other wins).
- Read: `keys()`, `values()`, `entries()` (`[[k,v],...]`), `count/size`, `isEmpty/isNotEmpty`.
- **Callback arg order differs — do not guess:**
  - `map` / `filter` / `tap`: `(value, key)`
  - `mapKeys` / `filterKeys`: `(key, value)`
  - `fold($init, fn)`: `(acc, value, key)`
- `equals(Map)` is order-independent, strict. `toArray()` keyed; `json_encode` → JSON object.

## Other collections (not detailed here — fetch the doc page when needed)
`NonEmptyVec`, `LazySeq`, `NonEmptyLazySeq`, `Range`, `Deque`, `PriorityQueue`, `NonEmptyMap`, `SortedMap`, `BiMap`, `MultiMap`, `Set`, `NonEmptySet`, `SortedSet`, `MultiSet`.
Choose: empty-invalid → `NonEmpty*`; deferred/bounded (`take`) → `LazySeq`; comparator order → `Sorted*`; counts → `MultiSet`; duplicate keys → `MultiMap`; reverse lookup → `BiMap`; both ends → `Deque`; priority → `PriorityQueue`; int interval without array → `Range`.
Non-empty variants stay non-empty under map/append; ops that can empty them return the ordinary type.

## Style rules when writing code with Exact
1. Prefer `fold()`/pattern match over `isSome()/get...()` branching.
2. Chain; avoid temp variables that hold stale pre-update collections.
3. Use `getOrElseGet(fn)` when the fallback is expensive or has side effects (`getOrElse` evaluates eagerly).
4. Use `Validated` for form-style multi-error, `Either` for sequential fail-fast pipelines. Don't use `Validated::andThen` expecting error accumulation.
5. Wrap throwing code at the boundary with `tryCatch`; keep domain logic exception-free.
6. Pass `static fn` closures; type-hint handler params (`Variant`, `Some`, ...).

## Lookup (cheapest path)
1. Search this file. 2. `grep -rn "function <name>" src/` and read only that method. 3. Fetch ONE raw doc, not the HTML page:
`https://github.com/pwaskito611/exact/raw/refs/heads/main/docs/usage/<section>/<file>.md`
Sections/files: `fundamentals/{option,either,validated,tuples}.md`, `modeling/adt.md`, `patterns/{error-handling,pattern-matching,domain-modeling}.md`, `collections/{overview,vec,non-empty-vec,map,set,lazy-seq,bimap,deque,multimap,multiset,non-empty-lazy-seq,non-empty-map,non-empty-set,priority-queue,range,sorted-map,sorted-set,map-variants,set-variants}.md`, `examples/{ecommerce,payment,registration}.md`.

## Verify
After edits: `php -l <file>` then `composer test` (or `vendor/bin/phpunit --filter <Name>`). Fix by reading the failing assertion, not the whole suite.

_Source: README + docs/usage at main (Oct 2026). Sections marked "verify" were not confirmed; Set/LazySeq/Deque/etc. and Tuples/ADT pages were not read._
