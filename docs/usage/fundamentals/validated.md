# Validated

`Validated` represents input that is either valid or invalid. `combine()` lets independent checks contribute errors through a callback.

## Quick Example

```php
use Exact\Data\Validated\Validated;

$issues = Validated::invalid(['name' => 'Required'])->combine(
    Validated::invalid(['email' => 'Invalid address']),
    static fn (mixed $name, mixed $email): array => [$name, $email],
    static fn (array $nameIssues, array $emailIssues): array => $nameIssues + $emailIssues,
);
```

The result is `Invalid` with both field errors. `combine()` calls the invalid callback only when both inputs are invalid, so independent checks can report together.

## When to Use Validated

Use it when independent input checks should be reported together, such as validating a registration form. Use [Result](result.md) when the first failure should stop a sequence of operations.

## Creating and Checking a Validation

```php
$name = Validated::valid('Ada');
$email = Validated::invalid('Email is required');

$hasName = $name->isValid();
$needsEmail = $email->isInvalid();
```

`valid()` and `invalid()` are the public factories. `isValid()` and `isInvalid()` return `bool`. The concrete `Valid` and `Invalid` constructors are protected, so create them through these factories.

## Combining Independent Checks

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

When both sides are valid, `combineValid` builds the output. When both are invalid, `combineInvalid` receives both payloads. If only one side is invalid, that payload is propagated unchanged; neither callback runs. Error accumulation is defined by the callback, not automatic.

## Transforming or Reading a Branch

`map()` transforms only a valid value; `mapInvalid()` transforms only an invalid payload. Both return `Validated`:

```php
$normalizedName = Validated::valid(' Ada ')
    ->map(static fn (string $value): string => trim($value));

$normalizedIssue = Validated::invalid('missing email')
    ->mapInvalid(static fn (string $issue): string => strtoupper($issue));
```

Use `fold()` to handle both branches. `getValid()` and `getInvalid()` extract a known branch and throw `LogicException` for the opposite branch. `get()` returns either payload without checking the branch; `getOrElse()` returns the valid value or a fallback.

```php
$displayName = Validated::valid('Ada')->getValid();
$firstIssue = Validated::invalid('Email is required')->getInvalid();
$fallbackName = Validated::invalid('Name is required')->getOrElse('Guest');
$rawPayload = Validated::invalid('Email is required')->get();
```

Use `fold()` when the branch is not already known:

```php
$email = Validated::invalid('Email is required');
$message = $email->fold(
    static fn (string $issue): string => "Cannot register: {$issue}",
    static fn (string $value): string => "Registering {$value}",
);
```

There is no `flatMap()` method. For sequential work that can fail, use [Result](result.md).

## Next Steps

- Use [Registration](../examples/registration.md) for a complete input flow.
- Compare Exact's outcome types in [Error Handling](../patterns/error-handling.md).