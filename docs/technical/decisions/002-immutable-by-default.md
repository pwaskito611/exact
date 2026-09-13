# ADR-002: Immutable-Style Data Values

## Status

Accepted as the current repository design.

## Context

Most data and eager collection classes are readonly, and their transformations return new values. `Matcher` remains mutable while its cases are registered.

## Decision

Favor readonly value state and non-mutating transformations for data and collections, while allowing a small mutable builder boundary for matcher registration.

## Alternatives

- Mutate collection and data objects in place.
- Claim deep immutability for all payloads and nested objects.

## Decision Rationale

Returning new values supports reuse of earlier pipeline stages and makes branch composition explicit. PHP readonly is shallow, so claiming deep immutability would exceed the implementation.

## Consequences

Callers must retain returned values, and transformations can allocate new arrays and objects. Payload objects remain subject to their own mutability.

## Revisit Conditions

Revisit this decision if collection representation, persistent data structures, or a deep-freezing contract is introduced.