# Result

`Result` represents an operation that either succeeds (`Ok`) or fails with a value (`Err`).

## Quick Example

```php
use Exact\Data\Result\Result;

$message = Result::ok(2500)
    ->flatMap(static fn (int $cents): Result => $cents > 0
        ? Result::ok($cents)
        : Result::err('Charge must be positive'))
    ->map(static fn (int $cents): string => sprintf('$%.2f', $cents / 100))
    ->mapErr(static fn (string $reason): string => strtoupper($reason))
    ->fold(
        static fn (string $error): string => "Payment failed: {$error}",
        static fn (string $amount): string => "Charge: {$amount}",
    );
```

The result is `Charge: $25.00`. If the amount is not positive, the `Err` skips success transformations and `fold()` formats the failure.

## When to Use It

Use `Result` when an operation can fail and callers need the failure value, such as parsing, authorization, or payment. Use [Option](option.md) when only presence or absence matters; use [Validated](validated.md) when independent input errors should be combined.

## Creating a Result

```php
use Exact\Data\Result\Err;
use Exact\Data\Result\Ok;

$success = Result::ok('R-204');
$failure = Result::err('Payment was declined');
$directSuccess = new Ok('R-205');
$directFailure = new Err('Card declined');
```

`ok()` and `err()` return a `Result`. `Ok` and `Err` also have public constructors, but the factories make the branch explicit while keeping code on the abstraction.

## Transforming or Chaining Work

`map()` changes only an `Ok` value. `mapErr()` changes only an `Err` value. Both return a `Result`:

```php
$normalized = Result::err('card declined')
    ->mapErr(static fn (string $error): string => strtoupper($error));
```

Use `flatMap()` when the next operation can also fail:

```php
$total = Result::ok(10)->flatMap(
    static fn (int $value): Result => $value > 0
        ? Result::ok($value + 5)
        : Result::err('Value must be positive'),
);
```

An `Err` passes through `map()` and `flatMap()` unchanged. On `Ok`, the `flatMap()` callback must return a `Result`, or a `TypeError` is thrown. `isOk()` and `isErr()` return `bool` when you need to inspect the branch:

```php
$saved = Result::ok('R-204')->isOk();
$rejected = Result::err('declined')->isErr();
```

## Handling Both Outcomes

Use `fold()` to turn either branch into one value. Its callbacks run in the order `onErr`, `onOk`:

```php
$result = Result::ok('R-204');
$label = $result->fold(
    static fn (mixed $error): string => "Failed: {$error}",
    static fn (string $receipt): string => "Receipt: {$receipt}",
);
```

`getOrElse()` returns the `Ok` value or a fallback. `get()` returns the value on `Ok` and throws `LogicException` on `Err`:

```php
$receipt = Result::ok('R-204')->get();
$receiptOrFallback = Result::err('declined')->getOrElse('not available');
$reason = Result::err('declined')->error();
```

`Err::error()` reads the error on a known `Err`. The public `Ok::error()` method always throws `LogicException`; use `fold()` or `isErr()` instead of probing the wrong branch.

## Next Steps

- Compare sequential failure with [Validated](validated.md).
- See [Error Handling](../patterns/error-handling.md) for choosing an outcome type.
- Follow a state-to-result workflow in [Payment](../examples/payment.md).