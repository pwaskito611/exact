# Testing Strategy

The repository uses PHPUnit and currently separates tests by the level of behavior they exercise.

## Unit

`tests/Unit/` covers one class or local operation at a time: branch behavior, collection operations, pattern dispatch, and exception construction/access.

## Feature

`tests/Feature/` exercises a public API workflow from a user's perspective. Examples include normalizing an optional email, combining validation, processing collections, and matching variants.

## Integration

`tests/Integration/` verifies interaction across abstractions. Current scenarios combine `Option`, `Validated`, and `Either`; combine `LazySeq` and `Vec`; and process an ADT variant through pattern matching.

## Test Runner

`phpunit.xml` bootstraps `vendor/autoload.php`, discovers the entire `tests` directory, and uses `.phpunit.cache`. The Composer script `composer test` runs `./vendor/bin/phpunit`.

There is no separate PHPUnit configuration for test layers, no coverage threshold, and no CI configuration in the repository.

## Test Boundaries

Tests should assert public behavior: final values, active branches, collection contents, domain equality, and documented exceptions. They should not couple to private property layout or internal call order unless that is itself the public contract.

## Related Documents

- [Property Testing](property-testing.md)
- [Mutation Testing](mutation-testing.md)
- [API Stability](../api-stability.md)