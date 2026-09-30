# Option

`Option` represents a value that may be present (`Some`) or absent (`None`).

## Quick Example

```php
use Exact\Data\Option\Option;

$input = 'ADA@example.com';
$domain = Option::from($input)
    ->filter(static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
    ->map(static fn (string $email): string => strtolower($email))
    ->map(static fn (string $email): string => explode('@', $email)[1])
    ->getOrElse('unknown');
```

The result is `example.com`. If the input is `null` or fails the filter, the mapping steps are skipped and the fallback is returned.

## When to Use It

Use `Option` when absence is expected and there is no failure detail to report, such as an optional profile field or lookup. Use [Result](result.md) when the reason an operation failed matters.

## Creating an Option

```php
$present = Option::some('Ada');
$missing = Option::none();
$fromInput = Option::from($input);
```

`some()` always creates `Some`, even for `null`. `none()` creates `None`. `from()` creates `None` only for `null`; values such as `''`, `0`, and `[]` create `Some`.

`Some` and `None` also have public constructors, but the factories keep callers working with the `Option` abstraction.

## Checking for a Value

Use the predicates when a branch check is useful:

```php
if ($fromInput->isSome()) {
    $email = $fromInput->getOrElse('');
}

$isMissing = Option::from(null)->isNone();
```

`isSome()` and `isNone()` return `bool` and do not extract the value.

## Transforming an Option

`map()` transforms a present value and returns an `Option`. `None` stays absent and does not call the callback:

```php
$normalized = Option::from(' ADA ')
    ->map(static fn (string $name): string => strtolower(trim($name)));
```

Use `flatMap()` when the next operation already returns an `Option`:

```php
$domain = Option::from('ada@example.com')->flatMap(
    static fn (string $email): Option => str_contains($email, '@')
        ? Option::some(explode('@', $email)[1])
        : Option::none(),
);
```

`filter()` keeps a `Some` only when its predicate returns `true`; otherwise it returns `None`:

```php
$validEmail = Option::from($input)
    ->filter(static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
```

## Getting a Value

`getOrElse()` returns the contained value or a raw default. `orElse()` returns the current `Option` when present, or the fallback `Option` when absent:

```php
$email = Option::from($input)->getOrElse('support@example.com');
$emailOption = Option::from($input)->orElse(Option::some('support@example.com'));
```

Use `fold()` when each branch needs different handling. It calls `onNone` for absence and `onSome` with the value for presence, then returns that callback's result:

```php
$label = Option::from($input)->fold(
    static fn (): string => 'No email',
    static fn (string $email): string => "Email: {$email}",
);
```

`Some::value()` is available only on the concrete `Some` type. Prefer the abstraction-level methods unless the branch has already been narrowed.

```php
use Exact\Data\Option\Some;
use Exact\Data\Option\None;

$some = new Some('ada@example.com');
$knownEmail = $some->value();
$none = new None();
```

## Next Steps

- Compare absence with failure in [Result](result.md).
- See [Registration](../examples/registration.md) for an end-to-end flow.
- Choose among Exact's outcome types in [Error Handling](../patterns/error-handling.md).