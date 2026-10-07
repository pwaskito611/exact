# API Stability

## Current Public Surface

The public API is defined by the classes and methods currently present under `src/`. Its main areas are:

- `Exact\Type`: `Option`, `Either`, `Validated`, `Tuple`, and `Pair`.
- `Exact\Collection`: eager, lazy, and specialized collection types such as `Vec`, `NonEmptyVec`, `Map`, `Set`, and `LazySeq`.
- `Exact\Pattern`: named variants and pattern-matching support.
- `Exact\Exception`: library-specific exceptions.

Composer maps the `Exact\` namespace to `src/`.

## Breaking Changes

The project has undergone a structural and API cleanup:

| Earlier API | Current API |
| --- | --- |
| `Exact\Data\...` | `Exact\Type\...` |
| `Exact\Adt\...` | `Exact\Pattern\...` |
| `Seq` | `Vec` |
| `NonEmptySeq` | `NonEmptyVec` |
| `Result` | Removed; use `Either` for right-biased alternatives or `Validated` to accumulate independent validation errors. |
| `Value` and `Function` modules | Removed from the library. |

The `Seq` and `NonEmptySeq` class aliases remain in the source for compatibility; new code and documentation should use `Vec` and `NonEmptyVec`. The namespace changes and removed modules are breaking changes for consumers of earlier APIs. The root README states that v2 is intended to keep a stable public API and prioritize backward compatibility; this cleanup is therefore a migration boundary into that API, not a guarantee that removed module names continue to work.

## Policy Status

The repository does not state a formal deprecation process or release cadence. The compatibility intent published for v2 is described in the root README.

## Documentation Boundary

Usage docs describe the current API. Technical docs describe observed contracts and limits; neither creates a stronger stability policy than the source, tests, or package metadata establish.
