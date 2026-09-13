# Pipe

`Pipe` carries a value through a sequence of transformations and returns a new pipe at each step.

## Basic Usage

```php
use Exact\Function\Pipe;

$result = Pipe::of(' exact ')
    ->map(static fn (string $value): string => trim($value))
    ->map(static fn (string $value): string => strtoupper($value))
    ->get();
```

The result is `EXACT`. `get()` extracts the current value.

## Through Functions

Use `through()` when functions are already available as callables:

```php
$value = Pipe::of(4)
    ->through(
        static fn (int $number): int => $number * 10,
        static fn (int $number): int => $number + 2,
    )
    ->get();
```

The original pipe is unchanged. See [Composition](composition.md) when the same transformations should be reused outside a pipe.

## Next Steps

- [Functions](functions.md)
- [Collections Overview](../collections/overview.md)