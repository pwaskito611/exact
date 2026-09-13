# Registration

A registration flow often has optional input, validation, a domain identifier, and a final success or failure result.

```php
use Exact\Data\Option\Option;
use Exact\Data\Result\Result;
use Exact\Data\Validated\Validated;
use Exact\Value\Newtype;

final readonly class RegistrationUserId extends Newtype
{
}

$id = new RegistrationUserId('user-42');
$email = Option::from('ADA@EXAMPLE.COM')
    ->map(static fn (string $value): string => strtolower($value));

$validatedEmail = $email->fold(
    static fn (): Validated => Validated::invalid('Email is required'),
    static fn (string $value): Validated => str_contains($value, '@')
        ? Validated::valid($value)
        : Validated::invalid('Email is invalid'),
);

$registration = $validatedEmail->fold(
    static fn (string $error): Result => Result::err($error),
    static fn (string $value): Result => Result::ok([
        'id' => $id,
        'email' => $value,
    ]),
);
```

The `Option` step handles absence, `Validated` represents input validity, and `Result` gives the caller a single success/error boundary. The `Newtype` prevents a raw string from being the only representation of the user identifier.

## Next Steps

- Read [Domain Modeling](../patterns/domain-modeling.md).
- Compare this flow with [Payment](payment.md).