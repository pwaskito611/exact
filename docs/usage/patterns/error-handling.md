# Error Handling

Exact provides distinct types for distinct domain states. Choose the type that expresses the workflow rather than treating every non-success outcome as the same kind of error.

| Type | Represents | Failure behavior |
|---|---|---|
| `Option` | Presence or absence | No error value; `None` carries no reason. |
| `Either` | Two domain branches | Right-biased transformations; both branches carry meaningful payloads. |
| `Validated` | Valid or invalid input | Invalid payloads can be combined with a caller-provided function. |
| `Variant` | A named state with a payload | Not an error type; dispatch by name with `PatternMatch`. |

## Choosing the Right Type

- Use `Option` for an optional lookup where absence is expected and no reason is needed.
- Use `Either` when an operation must preserve a meaningful alternative or failure payload, and the workflow should proceed through `Right`.
- Use `Validated` for form-like input where independent checks should report multiple errors together.
- Use `Either` when `Left` and `Right` describe domain alternatives rather than success and failure.
- Use `Variant` when the state is a named tag such as `Pending`, `Paid`, or `Failed` and needs name-based dispatch.

## Absence with Option

`Option` does not carry a reason for absence. Convert it to `Either` when the workflow has a reason to report:

```php
use Exact\Type\Option\Option;

$email = Option::from($input)->map('strtolower');

$message = $email->fold(
    static fn (): string => 'Email is missing',
    static fn (string $value): string => "Email: {$value}",
);

$either = $email->toEither('Email is required');
```

## Branching with Either

Use `Either` when a workflow carries a value forward on `Right` and preserves a meaningful alternative on `Left`:

```php
use Exact\Type\Either\Either;

$message = Either::right(10)
    ->flatMap(static fn (int $value): Either => $value > 0
        ? Either::right($value)
        : Either::left('Value must be positive'))
    ->map(static fn (int $value): int => $value * 2)
    ->fold(
        static fn (mixed $alternative): string => "Stopped: {$alternative}",
        static fn (int $value): string => "Value: {$value}",
    );
```

`flatMap()` continues only through `Right`; a `Left` is preserved unchanged. `Either` does not require `Left` to mean an error—the application defines the meaning of both branches.

Use `tap()` and `tapLeft()` to run effects without changing the `Either`:

```php
use Exact\Type\Either\Either;

$rightResult = Either::right(['id' => 42])->tap(
    static function (array $value): void {
        error_log('Received Right payload: ' . json_encode($value));
    },
);

$leftResult = Either::left('service unavailable')->tapLeft(
    static function (string $reason): void {
        error_log("Received Left payload: {$reason}");
    },
);
```

To recover from `Left`, use `orElse()`, `orElseGet()`, or `flatMapLeft()`:

```php
use Exact\Type\Either\Either;

$recovered = Either::left('cache miss')
    ->orElseGet(static fn (): Either => Either::right('loaded from source'));
```

## Independent Validation with Validated

Use `Validated` when input checks are independent and every error should be reported together. The accumulation rule is defined by a caller-provided callback; errors are not concatenated automatically.

```php
use Exact\Type\Validated\Validated;

$name = Validated::invalid('Name is required');
$email = Validated::invalid('Email is required');

$issues = $name->combine(
    $email,
    static fn (mixed $nameError, mixed $emailError): array => [
        $nameError,
        $emailError,
    ],
);
```

The invalid callback runs only when both inputs are invalid. If only one input is invalid, that payload is propagated unchanged.

For a collection of validations, use `sequence()` or `traverse()` with an error-combining callback:

```php
use Exact\Type\Validated\Validated;

$emailResults = Validated::traverse(
    ['ada@example.com', 'not-an-email'],
    static fn (string $value): Validated => str_contains($value, '@')
        ? Validated::valid($value)
        : Validated::invalid("Invalid email: {$value}"),
    static fn (array $errors): array => $errors,
);
```

`Validated` is applicative but not a monad: `andThen()` is fail-fast and does not accumulate errors across continuations. For sequential short-circuiting, prefer `Either`.

## Named States with Variant

Use `Variant` for a named state that is not itself an error:

```php
use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;
use Exact\Type\Either\Either;

$state = Variant::of('Failed', ['code' => 'DECLINED']);

$outcome = PatternMatch::on($state)
    ->case('Paid', static fn (Variant $value): Either => Either::right($value->value()))
    ->case('Failed', static fn (Variant $value): Either => Either::left($value->value()))
    ->run();
```

The named state can be one input to a larger workflow; `Either` is the outcome type when a branch must be preserved.

## Conversion Summary

| Conversion | Result | Information discarded |
|---|---|---|
| `Option` → `Either` | `toEither($left)` | The caller supplies the `Left` payload for absence. |
| `Either` → `Option` | `toOption()` | The `Left` payload. |
| `Validated` → `Either` | `toEither()` | The ability to accumulate errors in later validation combinations. |
| `Validated` → `Option` | `toOption()` | The invalid payload. |

Convert at the boundary where the workflow changes representation, not in the middle of a chain.

## Next Steps

- [Either](../fundamentals/either.md)
- [Validated](../fundamentals/validated.md)
- [Domain Modeling](domain-modeling.md)
- [Pattern Matching](pattern-matching.md)
