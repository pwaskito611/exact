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

Use `Either` when both sides are meaningful alternatives and the domain names them `Left` and `Right`. Its right-biased transformations make it useful for workflows that need a primary path and a typed alternative.

## Creating an Either

```php
$pickup = Either::left('pickup');
$courier = Either::right('courier');

$alsoRight = Either::of('courier');
$alsoRight = Either::pure('courier');

$fromNullable = Either::fromNullable($customer->email, 'Email is required');
$conditional = Either::cond($user->isActive(), $user, 'user is inactive');

$loaded = Either::tryCatch(
    static fn (): array => loadCustomerAccount(),
    static fn (Throwable $error): string => 'account lookup failed',
);
```

- `left()` and `right()` are the public factories; the constructor is private.
- `of()` and `pure()` are aliases for `right()`.
- `fromNullable()` maps `null` to `Left($left)` and other values to `Right`.
- `cond()` chooses `Right` or `Left` based on a condition.
- `tryCatch()` catches `Throwable` and maps it to `Left`. If no error callback is supplied, the caught throwable itself is stored as the `Left` payload.

## Checking Branches

```php
$isPickup = $pickup->isLeft();
$isCourier = $courier->isRight();
```

`isLeft()` and `isRight()` return `bool`.

## Transforming an Alternative

```php
$courier = Either::right('courier')
    ->map(static fn (string $method): string => strtoupper($method));

$pickup = Either::left('front desk')
    ->mapLeft(static fn (string $location): string => strtoupper($location));

$formatted = Either::right($userId)
    ->flatMap(
        static function (string $id) use ($userRepository): Either {
            $user = $userRepository->find($id);

            return $user !== null
                ? Either::right($user)
                : Either::left('user not found');
        },
    )
    ->map(static fn (User $user): string => $user->email());

$bothSides = Either::right('courier')->bimap(
    static fn (string $left): string => strtoupper($left),
    static fn (string $right): string => strtolower($right),
);

$recovered = Either::left('missing')->flatMapLeft(
    static fn (string $issue): Either => Either::right('fallback'),
);
```

`map()` transforms only `Right`; `mapLeft()` transforms only `Left`. `bimap()` transforms whichever branch is active. Use `flatMap()` or `flatMapLeft()` when the corresponding callback returns another `Either`; a non-`Either` result causes `TypeError` when that callback runs.

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
$maybeAmount = Either::right(2500)->getOrNull();
$maybeLeft = Either::left('missing invoice')->getLeftOrNull();
```

`orElse()` and `orElseGet()` supply alternative branches without losing the original `Right` success path. `getOrElseGet()` and `getOrElseThrow()` are explicit extraction helpers for when a value must be demanded from the current branch.

## Predicate, Inspection, and Combining

```php
$hasUser = Either::right($user)->exists(static fn (User $value): bool => $value->isActive());
$canShip = Either::right($address)->filter(
    static fn (Address $value): bool => $value->isValid(),
    'invalid shipping address',
);

$allPositive = Either::right(10)->forall(static fn (int $value): bool => $value > 0);
$isCourier = Either::right('courier')->contains('courier');
$combined = Either::right($order)
    ->zip(Either::right($invoice));
$sum = Either::right(10)->zipWith(
    Either::right(20),
    static fn (int $left, int $right): int => $left + $right,
);

$nested = Either::right(Either::right($receipt))->flatten();

$results = Either::sequence([Either::right('a'), Either::right('b')]);
$traversed = Either::traverse(
    [1, 2, 3],
    static fn (int $id): Either => Either::right($id * 2),
);
```

Use `filter()` to collapse a `Right` to `Left` when a predicate fails, `tap()` and `tapLeft()` for side effects without changing the value, and `zip()`, `zipWith()`, and `sequence()` / `traverse()` when you need to combine successful values or collections without losing fail-fast semantics.

## Interop

```php
$option = Either::right('courier')->toOption();
$reversed = Either::left('pickup')->swap();
```

`toOption()` keeps a `Right` payload as `Some` and discards a `Left` payload as `None`.

## Complete Method Reference

| Method | Description |
|---|---|
| `left()`, `right()` | Create either branch. |
| `of()`, `pure()` | Create `Right`. |
| `fromNullable()`, `cond()`, `tryCatch()` | Create a branch from nullable input, a condition, or a callback. |
| `isLeft()`, `isRight()` | Inspect the active branch. |
| `map()`, `mapLeft()`, `bimap()` | Transform one or both branches. |
| `flatMap()`, `flatMapLeft()` | Continue from `Right` or recover from `Left`. |
| `fold()` | Handle both branches and return a value. |
| `getOrElse()`, `getOrElseGet()`, `getOrElseThrow()` | Extract `Right` or provide a fallback/throw. |
| `getOrNull()`, `getLeftOrNull()` | Return a branch payload or `null`. |
| `getLeft()`, `getRight()` | Extract a known branch; throw `LogicException` for the opposite branch. |
| `get()`, `unsafeGet()` | Return the payload without checking its branch. |
| `orElse()`, `orElseGet()` | Supply an alternative when `Left`. |
| `filter()`, `exists()`, `forall()`, `contains()`, `equals()` | Inspect or compare `Right`. |
| `tap()`, `tapLeft()` | Run a side effect on one branch. |
| `ap()`, `zip()`, `zipWith()`, `flatten()` | Combine or flatten `Either` values. |
| `sequence()`, `traverse()` | Accumulate `Right` values, stopping at the first `Left`. |
| `toOption()`, `swap()` | Convert to `Option` or exchange branches. |

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

- Accumulate independent input errors with [Validated](validated.md).
- Match named states with [Pattern Matching](../patterns/pattern-matching.md).