# ADR-001: Framework-Independent Runtime

## Status

Accepted as the current repository design.

## Context

Exact is packaged as a Composer library. Its runtime `require` section contains only PHP `>=8.2`; the source has no framework or service-container dependency.

## Decision

Keep the core abstractions usable without a PHP framework or application lifecycle.

## Alternatives

- Integrate with a framework container or framework-specific value types.
- Require an external functional or collection runtime.

## Decision Rationale

The current public contracts operate on values, arrays, generators, and callables. Framework independence preserves that small boundary and lets applications decide how to integrate the values.

## Consequences

The package has no framework lifecycle to configure, but it also does not provide framework-specific adapters, dependency injection, persistence, or recovery conventions.

## Revisit Conditions

Revisit this decision if the core package begins requiring framework services, external runtime packages, or lifecycle integration.