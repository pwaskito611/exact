# Algebraic Data Types

Use `Variant` for a named domain state and `PatternMatch` to choose what to do for that state.

## Handle a State

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

`Variant::of($name, $value)` creates a state; the payload defaults to `null`. A matching case receives the payload. The default handler receives both the payload and the state name.

## Create and Inspect a Variant

```php
$state = Variant::of('Pending');

$stateName = $state->name();
$payload = $state->value();
$isPending = $state->is('Pending');
```

Case names are strings. `name()` and `value()` return the name and payload; `is()` returns `bool`. `Variant`'s constructor is private, so use `of()`.

`PatternMatch::on()` creates a `Matcher`. `case()` registers a handler and `default()` registers a fallback; both return the matcher so cases can be chained. Registering the same name again replaces its handler. `run()` returns the selected handler's result, or throws `MatchException` if no case or fallback handles the state. You can also construct a `Matcher` directly with a `Variant`.

## Next Steps

- See [Pattern Matching](../patterns/pattern-matching.md) for state workflows.
- Combine a variant with [Result](../fundamentals/result.md) in [Payment](../examples/payment.md).