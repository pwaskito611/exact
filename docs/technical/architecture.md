# Architecture

Exact is a framework-independent PHP library organized around small data, value, collection, and function abstractions. The source tree is divided into six conceptual modules:

```text
Exact
├── Data
├── Adt
├── Value
├── Collection
├── Function
└── Exception
```

## Module Responsibilities

### Data

`Data` models computational states and grouped values:

- `Option` models presence and absence.
- `Result` models success and error.
- `Either` models two named branches with right-biased transformations.
- `Validated` models valid and invalid input and can combine two invalid values.
- `Tuple` and `Pair` group positional values.

These types do not depend on collections, values, or the ADT matcher.

### Adt

`Variant` stores a string name and payload. `PatternMatch` creates a `Matcher` that dispatches the variant to a case or default handler. Matching is runtime dispatch and does not provide compile-time exhaustiveness.

### Value

`Newtype` gives one wrapped value a distinct concrete class and strict same-class equality. `ValueObject` defines an extension point where a subclass owns equality semantics.

### Collection

`Seq`, `NonEmptySeq`, `Map`, `Set`, and `LazySeq` represent ordered, keyed, unique, non-empty, and deferred data respectively. Eager collections use arrays; `LazySeq` uses deferred iterable production and materializes to `Seq` through `toList()`.

### Function

`Composition`, `Functions`, and `Pipe` operate on PHP callables. They provide function ordering, partial application, currying, and value-centered pipelines without introducing a framework runtime.

### Exception

The exception classes describe invalid access, empty collection access, and unmatched variants. They are used for programmer errors or invalid operations rather than for the normal `Option`, `Result`, `Either`, or `Validated` branch states.

## Dependency Direction

The current source has no production dependency on an external package and no module-wide service container or framework integration. Cross-module interaction occurs through public values and callables, for example a `Result` can contain a collection or a domain value without importing that module itself.

## Related Documents

- [Type Model](type-model.md)
- [Composition](composition.md)
- [Error Semantics](error-semantics.md)