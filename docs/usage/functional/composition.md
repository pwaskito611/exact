# Composition

`Composition` creates callables that apply several functions in a predictable order.

## Left-to-Right Piping

`pipe()` applies functions in the order they are supplied:

```php
use Exact\Function\Composition;

$normalize = Composition::pipe(
    static fn (string $value): string => trim($value),
    static fn (string $value): string => strtolower($value),
);

$email = $normalize(' ADA@EXAMPLE.COM ');
```

## Right-to-Left Composition

`compose()` applies functions from right to left:

```php
$format = Composition::compose(
    static fn (int $value): string => "Total: {$value}",
    static fn (int $value): int => $value * 2,
);

$message = $format(21); // Total: 42
```

Choose `pipe()` when reading the workflow in input order is clearer. Choose `compose()` when describing a final operation built from inner operations.

## Next Steps

- Combine composition with [Pipe](pipe.md).
- Apply transformations to [Seq](../collections/seq.md) or [LazySeq](../collections/lazy-seq.md).