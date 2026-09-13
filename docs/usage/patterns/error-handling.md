# Error Handling

Exact gives different names to different kinds of failure. Choosing the right type keeps the workflow understandable.

| Type | Represents | Failure behavior |
| --- | --- | --- |
| `Option` | Presence or absence | No error value. |
| `Result` | Success or failure | Carries an error value and short-circuits `map()`/`flatMap()`. |
| `Either` | Two domain branches | Right-biased transformations; both branches are meaningful. |
| `Validated` | Valid or invalid input | Two invalid values can be combined with a caller-provided function. |

## Sequential Failure with Result

Use `flatMap()` for steps where the next operation may fail:

```php
use Exact\Data\Result\Result;

$message = Result::ok(10)
    ->flatMap(static fn (int $value): Result => $value > 0
        ? Result::ok($value)
        : Result::err('Value must be positive'))
    ->map(static fn (int $value): int => $value * 2)
    ->fold(
        static fn (mixed $error): string => "Failed: {$error}",
        static fn (int $value): string => "Success: {$value}",
    );
```

An `Err` skips later success transformations. `mapErr()` can normalize an error without changing an `Ok` value.

## Independent Validation with Validated

Use `Validated::combine()` when input checks are independent and the invalid callback should receive both errors. See [Validated](../fundamentals/validated.md).

## Common Choice

- Use `Option` for an optional lookup where absence is expected.
- Use `Result` for an operation such as parsing, authorization, or payment where the failure matters.
- Use `Validated` for form-like input where multiple errors should be reported together.
- Use `Either` when the names `Left` and `Right` describe domain alternatives better than success and failure.

## Next Steps

- [Result](../fundamentals/result.md)
- [Validated](../fundamentals/validated.md)
- [Domain Modeling](domain-modeling.md)