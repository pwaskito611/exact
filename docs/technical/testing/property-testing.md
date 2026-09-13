# Property Testing

No property-testing framework, property-test directory, or generated property suite is currently present in the repository. The current tests are PHPUnit example-based tests.

## Current Evidence

The suite checks concrete invariants such as strict set uniqueness, non-empty sequence construction, lazy evaluation counts, branch short-circuiting, and non-mutating transformations. These are behavioral examples, not a declared property-testing system.

## Future Boundary

If property testing is introduced, candidate properties include:

- mapping preserves sequence order;
- `NonEmptySeq::map()` remains non-empty;
- `Set` output contains no strict duplicates;
- inactive data branches do not invoke callbacks;
- eager transformations leave the original value unchanged.

These are candidate properties only. This document does not claim they are currently generated or exhaustively tested.