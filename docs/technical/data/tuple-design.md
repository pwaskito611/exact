# Design: Tuples

## Purpose

`Tuple` groups a variable number of positional values. `Pair` is the fixed two-value specialization with `first` and `second` accessors.

## Contract

`Tuple::of()` accepts zero or more values, `at()` uses zero-based indexing, `count()` reports arity, and `map()` transforms every value. Missing indexes throw `OutOfBoundsException`.

`Pair::of()` always receives two values. `swap()` returns a new pair with positions reversed.

## Rationale and Trade-offs

Tuples are useful for small intermediate values without naming a domain class. They do not provide named fields or custom equality semantics, so define an application-owned domain class when a value needs those domain-specific properties.

## Testing Implications

Tests should distinguish variable-arity `Tuple` from fixed-arity `Pair`, verify index errors, mapping, and non-mutating swap behavior.