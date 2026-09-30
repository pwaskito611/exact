# Either

`Either` carries one of two meaningful alternatives. Its transformations are right-biased: `map()` and `flatMap()` work on `Right`, while `mapLeft()` works on `Left`.

## Quick Example

```php
use Exact\Data\Either\Either;

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

## Transforming an Alternative

```php
$courier = Either::right('courier')
    ->map(static fn (string $method): string => strtoupper($method));

$pickup = Either::left('front desk')
    ->mapLeft(static fn (string $location): string => strtoupper($location));
```

`map()` transforms only `Right`; `mapLeft()` transforms only `Left`. The other branch is returned unchanged. Use `flatMap()` when the `Right` callback returns another `Either`; a non-`Either` result causes `TypeError` when that callback runs.

```php
$delivery = Either::right('courier')->flatMap(
    static fn (string $method): Either => $method === 'courier'
        ? Either::right('tracked courier')
        : Either::left('unsupported delivery method'),
);
```

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