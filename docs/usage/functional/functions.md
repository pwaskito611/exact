# Functions

The `Functions` helpers create small reusable callables for functional workflows.

## Identity and Constants

```php
use Exact\Function\Functions;

$same = Functions::identity();
$fallback = Functions::constant('unknown');
```

`identity()` returns its input. `constant()` always returns the value supplied when the callable was created.

## Partial Application

`partial()` fixes the first arguments of a callable:

```php
$addTax = Functions::partial(
    static fn (int $tax, int $price): int => $price + $tax,
    2,
);

$total = $addTax(40); // 42
```

## Currying

`curry()` turns a callable into one that accepts one argument at a time until its arity is reached:

```php
$multiply = Functions::curry(
    static fn (int $a, int $b, int $c): int => $a * $b * $c,
    3,
);

$value = $multiply(2)(3)(4); // 24
```

The arity must be at least 1.

## Next Steps

- See [Composition](composition.md) for ordering multiple callables.
- See [Pipe](pipe.md) for a value-centered workflow.