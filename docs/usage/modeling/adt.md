# Algebraic Data Types

Exact models a named alternative with `Variant`. `PatternMatch` creates a `Matcher` that dispatches the variant to a handler.

## Basic Usage

```php
use Exact\Adt\PatternMatch;
use Exact\Adt\Variant;

$payment = Variant::of('Paid', ['receipt' => 'R-100']);

$message = PatternMatch::on($payment)
    ->case(
        'Paid',
        static fn (array $value): string => "Receipt: {$value['receipt']}",
    )
    ->case('Pending', static fn (): string => 'Waiting')
    ->default(static fn (mixed $value, string $name): string => "State: {$name}")
    ->run();
```

The case handler receives the variant value. The default handler receives both the value and the variant name.

## Defining and Inspecting Variants

```php
$state = Variant::of('Pending');

$state->name();
$state->value();
$state->is('Pending');
```

Case names are strings. If no case matches and no default handler exists, `run()` throws `MatchException`.

## Next Steps

- See [Pattern Matching](../patterns/pattern-matching.md) for state workflows.
- Combine a variant with [Result](../fundamentals/result.md) in [Payment](../examples/payment.md).