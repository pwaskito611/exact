# Domain Modeling

Exact is useful when a domain workflow has explicit states, typed values, and operations that may fail.

## A Small Workflow

The following flow gives an identifier its own type, validates input, and turns the validated value into a `Result`:

```php
use Exact\Data\Option\Option;
use Exact\Data\Result\Result;
use Exact\Data\Validated\Validated;
use Exact\Value\Newtype;

final readonly class UserId extends Newtype
{
}

$id = new UserId('user-42');
$email = Option::from('ADA@EXAMPLE.COM')
    ->map(static fn (string $value): string => strtolower($value));

$validated = $email->fold(
    static fn (): Validated => Validated::invalid('Email is required'),
    static fn (string $value): Validated => str_contains($value, '@')
        ? Validated::valid($value)
        : Validated::invalid('Email is invalid'),
);

$user = $validated->fold(
    static fn (string $error): Result => Result::err($error),
    static fn (string $value): Result => Result::ok(['id' => $id, 'email' => $value]),
);
```

This is a composition of public abstractions, not a required architecture. Keep the domain objects and callbacks as small as the workflow needs.

## Choosing the Abstraction

- Use a [Newtype](../modeling/newtypes.md) to distinguish one domain value from another.
- Use a [Value Object](../modeling/value-objects.md) when equality involves several fields.
- Use [Validated](../fundamentals/validated.md) for independent input checks.
- Use [Result](../fundamentals/result.md) for sequential domain operations that may fail.
- Use a [Variant](../modeling/adt.md) for named states such as `Pending`, `Paid`, and `Failed`.

## Next Steps

- Apply the pattern to [Registration](../examples/registration.md).
- See [Payment](../examples/payment.md) for variant-to-result handling.
- Process line items with [Collections](../collections/overview.md).