# Option

`Option` represents either a value (`Some`) or absence (`None`) without requiring a nullable value at every call site.

## When to Use It

Use `Option` when absence is a normal outcome and no error explanation is needed. Use [Result](result.md) when failure information matters.

## Basic Usage

```php
use Exact\Data\Option\Option;

$present = Option::some('Ada');
$missing = Option::none();
$fromInput = Option::from($input);
```

`Option::from(null)` creates `None`; an empty array or another non-null value creates `Some`.

## Transforming Values

`map()` transforms a `Some` value. `None` stays absent and does not run the callback.

```php
$normalized = Option::from(' ADA ')
    ->map(static fn (string $name): string => strtolower(trim($name)));
```

Use `flatMap()` when the callback itself returns an `Option`:

```php
$domain = Option::from('example.com')
    ->flatMap(static fn (string $host): Option => str_contains($host, '.')
        ? Option::some($host)
        : Option::none());
```

Use `filter()` to keep a `Some` only when a predicate is true.

## Extracting a Value

```php
$label = Option::from($name)->getOrElse('Anonymous');

$description = Option::from($name)->fold(
    static fn (): string => 'No name',
    static fn (string $value): string => "Name: {$value}",
);
```

`orElse()` supplies another `Option` rather than a raw fallback.

## Next Steps

- Compare it with [Result](result.md).
- Use it in a [Registration workflow](../examples/registration.md).
- See [Error Handling](../patterns/error-handling.md) for combined workflows.