# Registration

A registration flow often has optional input, validation, and an explicit outcome.

```php
use Exact\Type\Option\Option;
use Exact\Type\Either\Either;
use Exact\Type\Validated\Validated;

$id = 'user-42';
$email = Option::from('ADA@EXAMPLE.COM')
    ->map(static fn (string $value): string => strtolower($value));

$validatedEmail = $email->fold(
    static fn (): Validated => Validated::invalid('Email is required'),
    static fn (string $value): Validated => str_contains($value, '@')
        ? Validated::valid($value)
        : Validated::invalid('Email is invalid'),
);

$registration = $validatedEmail->fold(
    static fn (string $error): Either => Either::left($error),
    static fn (string $value): Either => Either::right([
        'id' => $id,
        'email' => $value,
    ]),
);
```

The `Option` step handles absence, `Validated` represents input validity, and `Either` gives the caller a typed `Left`/`Right` outcome. In a real application, the identifier can be represented by an application-owned domain class.

## Next Steps

- Read [Domain Modeling](../patterns/domain-modeling.md).
- Compare this flow with [Payment](payment.md).