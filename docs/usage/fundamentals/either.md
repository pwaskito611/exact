# Either

`Either` carries one of two meaningful alternatives. Its transformations are right-biased: `map()` and `flatMap()` work on `Right`, while `mapLeft()` works on `Left`.

## Quick Example

```php
use Exact\Type\Either\Either;

$deliveryLabel = Either::right('courier')
    ->map(static fn (string $method): string => strtoupper($method))
    ->fold(
        static fn (string $method): string => "Collect at {$method}",
        static fn (string $method): string => "Ship by {$method}",
    );
```

This keeps the two domain alternatives explicit. `Either` does not decide that `Left` means failure; your domain assigns meaning to each side.

## When to Use Either

Use `Either` when both sides are meaningful alternatives and the domain names them `Left` and `Right`. Use [Result](result.md) when the branches specifically mean success and failure.

## Creating and Checking Branches

```php
$pickup = Either::left('pickup');
$courier = Either::right('courier');

$isPickup = $pickup->isLeft();
$isCourier = $courier->isRight();
```

`left()` and `right()` are the public factories; the constructor is private. `isLeft()` and `isRight()` return `bool`.

## Creating an Either from Data and Exceptions

```php
$customerEmail = Either::fromNullable($customer->email, 'Email is required');
$loadAccount = Either::tryCatch(
    static fn (): array => loadCustomerAccount(),
    static fn (Throwable $error): string => 'account lookup failed',
);
```

`fromNullable()` turns `null` into `Left($default)`, while `tryCatch()` converts thrown exceptions into a `Left` payload without re-throwing them. `of()` and `pure()` are explicit aliases for `right()` when a value is already known to be the successful branch.

## Transforming an Alternative

```php
$courier = Either::right('courier')
    ->map(static fn (string $method): string => strtoupper($method));

$pickup = Either::left('front desk')
    ->mapLeft(static fn (string $location): string => strtoupper($location));

$formatted = Either::right($userId)
    ->flatMap(
        static fn (string $id): Either => userRepository->find($id)
            ? Either::right($user)
            : Either::left('user not found'),
    )
    ->map(static fn (User $user): string => $user->email());
```

`map()` transforms only `Right`; `mapLeft()` transforms only `Left`. The other branch is returned unchanged. Use `flatMap()` when the `Right` callback returns another `Either`; a non-`Either` result causes `TypeError` when that callback runs.

```php
$delivery = Either::right('courier')->flatMap(
    static fn (string $method): Either => $method === 'courier'
        ? Either::right('tracked courier')
        : Either::left('unsupported delivery method'),
);
```

## Recovery and Extraction

```php
$resolved = Either::left('payment declined')
    ->orElseGet(static fn (): Either => Either::right('retry payment'));

$amount = Either::right(2500)->getOrElseGet(static fn (): int => 0);
$invoice = Either::left('missing invoice')->getOrElseThrow(new RuntimeException('invoice missing'));
```

`orElse()` and `orElseGet()` supply alternative branches without losing the original `Right` success path. `getOrElseGet()` and `getOrElseThrow()` are explicit extraction helpers for when a value must be demanded from the current branch.

## Predicate, Inspection, and Combining

```php
$hasUser = Either::right($user)->exists(static fn (User $value): bool => $value->isActive());
$canShip = Either::right($address)->filter(
    static fn (Address $value): bool => $value->isValid(),
    'invalid shipping address',
);

$combined = Either::right($order)
    ->zip(Either::right($invoice));

$nested = Either::right(Either::right($receipt))->flatten();
```

Use `filter()` to collapse a `Right` to `Left` when a predicate fails, `tap()` and `tapLeft()` for side effects without changing the value, and `zip()`, `zipWith()`, and `sequence()` / `traverse()` when you need to combine successful values or collections without losing fail-fast semantics.

## Handling Either Branch

`fold()` calls `onLeft` or `onRight` and returns that callback's result. `getOrElse()` returns the `Right` payload or a raw fallback:

```php
$label = Either::left('front desk')->fold(
    static fn (string $location): string => "Collect at {$location}",
    static fn (string $method): string => "Ship by {$method}",
);

$method = Either::right('courier')->getOrElse('pickup');
```

`getLeft()` and `getRight()` extract a known branch and throw `LogicException` on the opposite branch. `get()` returns the payload without checking which side contains it; prefer `fold()` when the distinction matters. `swap()` returns a new `Either` with the same payload on the opposite side:

```php
$leftValue = Either::left('pickup')->getLeft();
$rightValue = Either::right('courier')->getRight();
$payload = Either::right('courier')->get();
$reversed = Either::left('pickup')->swap();
```

## Next Steps

- Compare two-sided domain alternatives with [Result](result.md).
- Match named states with [Pattern Matching](../patterns/pattern-matching.md).