# Public API Usage Documentation Audit

This second-pass inventory was rebuilt from Composer-loaded source using PHP reflection, then checked against `docs/usage`. It records public non-helper types, their public operations, where they are explained, and deliberate exclusions. The usage pages describe behavior and workflows rather than duplicating signatures.

## Audited Usage API

| Type | Public API audited | Usage coverage |
| --- | --- | --- |
| `Option` | `some()`, `none()`, `from()`, `isSome()`, `isNone()`, `map()`, `flatMap()`, `filter()`, `fold()`, `getOrElse()`, `orElse()`; `Some::__construct()`, `Some::value()`, `None::__construct()` | [Option](../usage/fundamentals/option.md) |
| `Result` | `ok()`, `err()`, `isOk()`, `isErr()`, `map()`, `mapErr()`, `flatMap()`, `fold()`, `getOrElse()`, `get()`, `Ok::__construct()`, `Ok::error()`, `Err::__construct()`, `Err::error()` | [Result](../usage/fundamentals/result.md) |
| `Either` | `left()`, `right()`, `isLeft()`, `isRight()`, `map()`, `mapLeft()`, `flatMap()`, `fold()`, `getOrElse()`, `getLeft()`, `getRight()`, `get()`, `swap()` | [Either](../usage/fundamentals/either.md) |
| `Validated` | `valid()`, `invalid()`, `isValid()`, `isInvalid()`, `Valid::isValid()`, `Invalid::isValid()`, `map()`, `mapInvalid()`, `fold()`, `getOrElse()`, `getValid()`, `getInvalid()`, `get()`, `combine()` | [Validated](../usage/fundamentals/validated.md) |
| `Tuple` | `of()`, `at()`, `count()`, `map()`, `toArray()` | [Tuples](../usage/fundamentals/tuples.md) |
| `Pair` | `of()`, `first()`, `second()`, `swap()`, `toArray()` | [Tuples](../usage/fundamentals/tuples.md) |
| `Newtype` | public constructor, `value()`, `equals()` | [Newtypes](../usage/modeling/newtypes.md) |
| `ValueObject` | abstract `equals()` contract | [Value Objects](../usage/modeling/value-objects.md) |
| `Variant` | `of()`, `name()`, `value()`, `is()` | [Algebraic Data Types](../usage/modeling/adt.md) |
| `PatternMatch` | `on()` | [Pattern Matching](../usage/patterns/pattern-matching.md) |
| `Matcher` | public constructor, `case()`, `default()`, `run()` | [Pattern Matching](../usage/patterns/pattern-matching.md) |
| `Seq` | `empty()`, `of()`, `fromArray()`, `isEmpty()`, `isNotEmpty()`, `size()`, `head()`, `tail()`, `get()`, `map()`, `filter()`, `flatMap()`, `foldLeft()`, `contains()`, `append()`, `prepend()`, `toArray()`, `each()` | [Seq](../usage/collections/seq.md) |
| `NonEmptySeq` | `of()`, `fromArray()`, `head()`, `tail()`, `size()`, `map()`, `filter()`, `append()`, `prepend()`, `toArray()` | [NonEmptySeq](../usage/collections/non-empty-seq.md) |
| `Map` | `empty()`, `of()`, `fromArray()`, `has()`, `get()`, `getOrElse()`, `put()`, `remove()`, `size()`, `isEmpty()`, `map()`, `toArray()` | [Map](../usage/collections/map.md) |
| `Set` | `empty()`, `of()`, `add()`, `remove()`, `contains()`, `size()`, `isEmpty()`, `union()`, `intersect()`, `map()`, `toArray()` | [Set](../usage/collections/set.md) |
| `LazySeq` | `from()`, `defer()`, `map()`, `filter()`, `take()`, `toList()` | [LazySeq](../usage/collections/lazy-seq.md) |
| `Pipe` | `of()`, `through()`, `map()`, `get()` | [Pipe](../usage/functional/pipe.md) |
| `Composition` | `compose()`, `pipe()` | [Composition](../usage/functional/composition.md) |

## Second-Pass Action Matrix

| Source API group | Documented | Practical examples | UX action |
| --- | --- | --- | --- |
| `Option`, `Result`, `Either`, `Validated` | Yes | Yes, including branch operations and extraction | `KEEP` after task-oriented rewrite |
| `Tuple`, `Pair`, `Newtype`, `ValueObject` | Yes | Yes, using domain values where appropriate | `KEEP` |
| `Variant`, `PatternMatch`, `Matcher` | Yes | Yes, including direct `Matcher` construction | `KEEP` |
| `Seq`, `NonEmptySeq`, `Map`, `Set`, `LazySeq` | Yes | Yes, covering factories, queries, transformations, and updates | `KEEP` after task-oriented rewrite |
| `Pipe`, `Composition` | Yes | Yes | `KEEP` |
| `Functions` helpers | Excluded by scope | Existing examples remain in the guide | `EXCLUDE` from the non-helper audit |
| Exception construction APIs | Triggering behavior documented where relevant; unused `InvalidStateException` excluded | Constructors are infrastructure rather than normal workflows | `EXCLUDE` from usage API coverage |

The source scan found no public enum or interface declarations and no remaining in-scope non-helper type without a usage page. Installation guidance was also corrected to match the Composer package name and available PHPUnit command.

## Excluded From Usage Coverage

| Public type or API | Reason |
| --- | --- |
| `Exact\Function\Functions` (`identity()`, `constant()`, `curry()`, `partial()`) | Function helpers are explicitly outside this audit's target. |
| `InvalidStateException` | Public exception class is not thrown or otherwise referenced by the current library implementation; documenting manual construction would not describe a supported Exact workflow. |
| `MatchException::noMatch()` and `MatchException::notExhaustive()` | Exception factories support library control flow rather than normal user workflows. `Matcher::run()` and its observable no-match exception are covered in [Pattern Matching](../usage/patterns/pattern-matching.md); `notExhaustive()` has no current call site. |
| Exception constructors (`EmptyCollectionException`, `InvalidStateException`, `MatchException`) | These are infrastructure exception construction APIs. Observable throws and their triggering operations are documented at the relevant usage pages rather than encouraging callers to construct them. |
| Inherited/default constructors and protected/private constructors | Abstract or protected constructors are not user construction APIs. Private constructors are reached through their documented factories. Public constructors on `Some`, `None`, `Ok`, `Err`, and `Newtype` are audited above; `Valid` and `Invalid` inherit a protected constructor. Implicit default constructors on stateless utility classes such as `PatternMatch` and `Composition` carry no workflow state; use their documented static entry points. |

## Function Helper Source

`Exact\Function\Functions` is the only class in `src/Function` whose methods are standalone function helpers. Its methods are excluded by scope. The source inventory otherwise contains the audited types above and the three exception classes listed here; no public enum or interface declarations are present.
