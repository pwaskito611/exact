# API Stability

## Public Surface

The public surface is the set of public classes and methods under `src/`, including data types, collections, function helpers, ADT values/matching, value abstractions, and exception classes. Composer maps the `Exact\` namespace to `src/`.

## Current Policy Status

The repository does not state a formal semantic-versioning policy, deprecation process, release cadence, or backward-compatibility guarantee. The root README describes the project as still evolving and says the API may change.

## Compatibility Expectations

Tests document current behavior and should be updated deliberately when a contract changes. A breaking change includes changing branch semantics, exception behavior, return types, namespace paths, or collection invariants. No compatibility promise beyond the repository's current declarations should be inferred.

## Documentation Boundary

Usage docs describe how to consume the current API. These technical docs describe contracts and limits. Neither creates a stronger stability policy than the source, tests, or package metadata establish.