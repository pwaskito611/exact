# Static Analysis

No PHPStan, Psalm, or other static-analysis configuration is present in the repository. `composer.json` declares PHPUnit only as a development dependency.

## Type Evidence in Source

The source still uses PHP declarations, readonly classes/properties, union types, `mixed`, `never`, callable signatures, and PHPDoc array/callable annotations. These provide runtime and editor-facing information, but they do not amount to a configured static-analysis gate.

## Consequence

Contributors should treat PHP runtime checks, PHPUnit tests, and the declared signatures as current validation mechanisms. Generic relationships described in technical docs are not enforced by a configured analyzer.