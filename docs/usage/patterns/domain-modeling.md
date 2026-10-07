# Domain Modeling

Exact is useful when a domain workflow has explicit states, alternatives, and validation outcomes. Each abstraction has a focused role: `Option` for presence, `Validated` for input checks, `Either` for right-biased flows, and `Variant` for named states. Compose them where they make a workflow clearer; they are not a required architecture.

## A Small Workflow

This flow represents optional input, validates it, then converts the outcome into an `Either`:

```php
use Exact\Type\Either\Either;
use Exact\Type\Option\Option;
use Exact\Type\Validated\Validated;

$id = 'user-42';
$email = Option::from('ADA@EXAMPLE.COM')
    ->map(static fn (string $value): string => strtolower($value));

$validated = $email->fold(
    static fn (): Validated => Validated::invalid('Email is required'),
    static fn (string $value): Validated => str_contains($value, '@')
        ? Validated::valid($value)
        : Validated::invalid('Email is invalid'),
);

$user = $validated->fold(
    static fn (string $error): Either => Either::left($error),
    static fn (string $value): Either => Either::right([
        'id' => $id,
        'email' => $value,
    ]),
);
```

Each step is explicit about what it represents:

- `Option::from()` treats `null` as absence; `''`, `0`, and `[]` are still `Some`.
- `Validated` expresses the input rule and can combine independent checks with a caller-provided error-combining callback.
- `Either` carries the resulting value or meaningful alternative through the rest of the workflow.

## Choosing the Abstraction

| Question | Abstraction |
|---|---|
| Is the value present or absent, with no failure detail? | `Option` |
| Are both sides meaningful and named `Left` / `Right`? | `Either` |
| Should independent input checks report multiple errors together? | `Validated` |
| Is the state a named tag with a payload (`Pending`, `Paid`, `Failed`)? | `Variant` |

See the dedicated guides:

- [Validated](../fundamentals/validated.md) for independent input checks.
- [Either](../fundamentals/either.md) for right-biased domain workflows with an explicit alternative.
- [Variant / ADT](../modeling/adt.md) for named states such as `Pending`, `Paid`, and `Failed`.

## Mixing Types

These types expose operations such as `fold()` and `map()`, as well as conversions like `toEither()` and `toOption()`. Convert where the workflow changes representation:

```php
use Exact\Type\Either\Either;
use Exact\Type\Option\Option;
use Exact\Type\Validated\Validated;

$input = 'ada@example.com';

$email = Option::from($input)
    ->toEither('Email is required');

$validated = $email->fold(
    static fn (string $error): Validated => Validated::invalid($error),
    static fn (string $value): Validated => Validated::valid($value)->filter(
        static fn (string $candidate): bool => str_contains($candidate, '@'),
        'Email is invalid',
    ),
);
$outcome = $validated->toEither();

$message = $outcome->fold(
    static fn (string $error): string => "Rejected: {$error}",
    static fn (string $value): string => "Accepted: {$value}",
);
```

Prefer converting once at a representation boundary rather than converting back and forth. Each conversion can discard information; for example, `toOption()` discards an `Either`'s `Left` payload.

## Keeping Callbacks Small

Keep important decisions in named functions or domain methods when inline callbacks would obscure them:

```php
use Exact\Type\Option\Option;
use Exact\Type\Validated\Validated;

final class EmailPolicy
{
    public static function normalize(string $value): string
    {
        return strtolower(trim($value));
    }

    public static function isDeliverable(string $value): bool
    {
        return str_contains($value, '@');
    }
}

$validated = Option::from($rawInput)
    ->map(EmailPolicy::normalize(...))
    ->fold(
        static fn (): Validated => Validated::invalid('Email is required'),
        static fn (string $email): Validated => Validated::valid($email)->filter(
            EmailPolicy::isDeliverable(...),
            'Email is invalid',
        ),
    );
```

The chain reads as a policy statement, while the implementation can be tested independently.

## Next Steps

- Apply these ideas to [Registration](../examples/registration.md).
- See [Payment](../examples/payment.md) for variant matching and typed outcomes.
- Process line items with [Collections](../collections/overview.md).
