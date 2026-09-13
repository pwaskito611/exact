# Validated

`Validated` represents a `Valid` value or an `Invalid` value. Unlike a short-circuiting `Result` chain, two invalid values can be combined with a callback supplied by the caller.

## When to Use It

Use `Validated` when independent inputs should be checked together. Use [Result](result.md) when the first failure should stop a sequential operation.

## Basic Usage

```php
use Exact\Data\Validated\Validated;

$validName = Validated::valid('Ada');
$invalidEmail = Validated::invalid('Email is invalid');
```

## Combining Validation

```php
$profile = Validated::valid('Ada')->combine(
    Validated::valid('ada@example.com'),
    static fn (string $name, string $email): array => [
        'name' => $name,
        'email' => $email,
    ],
    static fn (mixed $first, mixed $second): array => [$first, $second],
);
```

When both values are valid, `combineValid` builds the result. When both are invalid, `combineInvalid` receives both errors. If only one side is invalid, that invalid value is propagated.

## Mapping and Extraction

`map()` transforms a valid value; `mapInvalid()` transforms an error value. `fold()` handles both branches:

```php
$message = $profile->fold(
    static fn (mixed $error): string => 'Invalid input',
    static fn (array $value): string => $value['email'],
);
```

Use `getValid()` or `getInvalid()` when the branch is already known. The opposite accessor throws `LogicException`.

## Next Steps

- See [Registration](../examples/registration.md).
- Compare validation accumulation with [Error Handling](../patterns/error-handling.md).