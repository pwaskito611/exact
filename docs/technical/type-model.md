# Type Model

Exact builds domain-oriented contracts from PHP 8.2 language features. This document separates PHP capabilities from conventions imposed by Exact.

## PHP Capabilities Used

- `abstract` classes define branch-oriented base contracts such as `Option`, `Result`, `Validated`, and `ValueObject`.
- `final` classes close concrete implementations such as `Some`, `None`, `Ok`, `Err`, `Variant`, and the collections.
- `readonly` classes and readonly properties protect object properties from reassignment after construction.
- Union types are used for APIs such as `Map` keys (`string|int`).
- `mixed` is used where the library intentionally supports arbitrary payloads.
- `never` is used by accessors that always throw, such as `Ok::error()` and `Err::get()`.
- PHPDoc describes array and callable shapes, but these annotations are not enforced by the PHP runtime.

The source contains no interfaces or enums, and no generic type parameters. It also does not use intersection types or reflection-based type registration.

## Exact Conventions

The branch classes encode state in the concrete runtime class or internal branch flag:

```text
Option    -> Some | None
Result    -> Ok | Err
Validated -> Valid | Invalid
Either    -> Left | Right state inside Either
```

`Variant` uses a runtime string name rather than a PHP enum case. `Matcher` narrows behavior by looking up that name, but PHP does not statically prove that all names have been handled.

## Type Narrowing

Methods such as `isOk()`, `isSome()`, `isLeft()`, and `isValid()` communicate branch state to callers at runtime. `fold()` is the primary branch-elimination operation because it requires handlers for both branches and returns one final value.

`flatMap()` adds a runtime contract: its callback must return the same abstraction. `Result`, `Either`, and `Seq` explicitly throw `TypeError` for invalid return values; `Option::flatMap()` relies on the declared return type and callback behavior.

## Limits

PHP's runtime does not track the value types inside `mixed` payloads. The current project has no configured PHPStan, Psalm, or other static-analysis gate, so generic-looking relationships such as `Result<T, E>` are conceptual documentation notation, not enforced source-level generics.

## Related Documents

- [Data Type Design](data/option-design.md)
- [ADT Design](adt/adt-design.md)
- [PHP Compatibility](php-compatibility.md)