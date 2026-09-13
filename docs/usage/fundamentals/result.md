# Result

`Result` represents either a successful `Ok` value or an `Err` value carrying failure information.

## When to Use It

Use `Result` when an operation can fail and callers need the failure value. Use [Option](option.md) when only presence or absence matters.

## Basic Usage

```php
use Exact\Data\Result\Result;

$success = Result::ok(42);
$failure = Result::err('Payment was declined');
```

## Transforming a Successful Value

`map()` runs only for `Ok`. `mapErr()` runs only for `Err`.

```php
$result = Result::ok(20)
    ->map(static fn (int $value): int => $value * 2)
    ->mapErr(static fn (string $error): string => strtoupper($error));
```

Use `flatMap()` to continue with an operation that returns another `Result`:

```php
$total = Result::ok(10)->flatMap(
    static fn (int $value): Result => $value > 0
        ? Result::ok($value + 5)
        : Result::err('Value must be positive'),
);
```

An `Err` short-circuits later `map()` and `flatMap()` callbacks.

## Handling Both Branches

Use `fold()` when a workflow must produce one final value:

```php
$message = $result->fold(
    static fn (mixed $error): string => "Failed: {$error}",
    static fn (int $value): string => "Total: {$value}",
);
```

`getOrElse()` returns the `Ok` value or a fallback. `get()` returns the contained value regardless of branch; use `fold()` when branch-specific behavior is clearer.

## Next Steps

- See [Error Handling](../patterns/error-handling.md).
- Combine `Result` with [Newtypes](../modeling/newtypes.md) in [Domain Modeling](../patterns/domain-modeling.md).
- See [Payment](../examples/payment.md).