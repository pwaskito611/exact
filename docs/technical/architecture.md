# Architecture

Exact is a framework-independent PHP data-modeling library focused on Algebraic Data Types, immutable collections, and explicit pattern matching. The source tree is divided into four conceptual modules:

```text
Exact
├── Type
├── Collection
├── Pattern
└── Exception
```

## Module Responsibilities

### Type

`Type` models domain states and grouped values:

- `Option` models presence and absence.
- `Either` models two named alternatives with right-biased transformations.
- `Validated` models valid and invalid input and can combine two invalid values.
- `Tuple` and `Pair` group positional values.

### Pattern

`Variant` stores a string name and payload. `PatternMatch` creates a `Matcher` that dispatches the variant to a case or default handler. Matching is runtime dispatch and does not provide compile-time exhaustiveness.

### Collection

`Vec`, `NonEmptyVec`, `Map`, `Set`, `Deque`, and other collection types represent ordered, keyed, unique, double-ended, and related data structures. Eager collections use arrays; `LazySeq` defers iterable production and materializes to `Vec` through `toSeq()`.

### Exception

The exception classes describe invalid access, empty collection access, and unmatched variants. They are used for programmer errors or invalid operations rather than normal `Option`, `Either`, or `Validated` states.

## Dependency Direction

The current source has no production dependency on an external package and no module-wide service container or framework integration. Cross-module interaction occurs through public values: a type can contain a collection or an application-owned domain value without coupling the modules to one another.

## Related Documents

- [Type Model](type-model.md)
- [Composition](composition.md)
- [Error Semantics](error-semantics.md)