# Algebraic Data Types

Use `Variant` for a named domain state and `PatternMatch` to choose what to do for that state. Class-based ADTs (`Some`, `None`, `Left`, `Right`) can also be matched by their class names. `Variant` is useful when the set of cases is not known at compile time.

## Quick Example

```php
use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;

$payment = Variant::of('Paid', ['receipt' => 'R-100']);

$message = PatternMatch::on($payment)
    ->case(
        'Paid',
        static fn (Variant $value): string => "Receipt: {$value->value()['receipt']}",
    )
    ->case('Pending', static fn (Variant $value): string => 'Waiting')
    ->default(static fn (Variant $value): string => "State: {$value->name()}")
    ->run();
```

Every handler receives the subject itself. For a `Variant`, use `name()` and `value()` to inspect its case and payload.

## When to Use It

Use `Variant` with `PatternMatch` when:

- Cases are defined at runtime, such as plugin states or values decoded from external data.
- Multiple guarded handlers are useful for one case.
- You want to check that a known set of cases has handlers.

For statically known ADTs such as `Option` and `Either`, prefer their concrete types so the PHP type system can reason about the values.

## Creating and Inspecting a Variant

```php
use Exact\Pattern\Variant;

$paid = Variant::of('Paid', ['receipt' => 'R-100']);
$pending = Variant::unit('Pending');

$name = $paid->name();
$payload = $paid->value();
$isPending = $pending->is('Pending');
$matchKey = $paid->matchKey();
```

`Variant::of(string $name, mixed $value = null)` creates a named case with an optional payload. `Variant::unit(string $name)` creates an enum-like case whose payload is `null`. The constructor is private; use these factories.

- `name()` returns the case name.
- `value()` returns the payload.
- `is($name)` checks the case name using strict equality.
- `matchKey()` returns the case name for dispatch by `Matcher`.

`Variant` is immutable. Its `equals()` method compares both the name and payload using strict equality. `jsonSerialize()` returns an array with `name` and `value` keys. String conversion returns just the name for a `null` payload; otherwise it renders `Name(payload)` for scalar payloads and `Name(type)` for non-scalar payloads. Scalar payloads use PHP's `var_export()` representation:

```php
$same = Variant::of('Paid', 100)->equals(Variant::of('Paid', 100));
$json = json_encode(Variant::of('Paid', 100));
$label = (string) Variant::of('Paid', 100); // Paid(100)
$unitLabel = (string) Variant::unit('Pending'); // Pending
```

## Beginning a Match

```php
use Exact\Pattern\PatternMatch;

$matcher = PatternMatch::on($payment);
$checked = PatternMatch::on(
    $payment,
    exhaustive: ['Paid', 'Pending', 'Failed'],
);
```

`PatternMatch::on(object $subject, ?array $exhaustive = null)` creates a `Matcher`. Supplying `$exhaustive` is equivalent to calling `exhaustive()` on the new matcher. You can also construct a `Matcher` directly:

```php
use Exact\Pattern\Matcher;

$matcher = new Matcher($payment);
```

## Registering Cases

```php
$paymentWithAmount = Variant::of('Paid', ['amount' => 150]);

$result = PatternMatch::on($paymentWithAmount)
    ->case('Paid', static fn (Variant $value): string => 'receipt')
    ->case(['Pending', 'Processing'], static fn (Variant $value): string => 'in progress')
    ->run();
```

`case(string|array $keys, callable $handler)` registers one handler for one key or a list of keys. Every handler receives the subject, so type-hint it as the expected class where practical.

### Guarded Cases

```php
$result = PatternMatch::on($payment)
    ->caseWhen(
        'Paid',
        static fn (Variant $value): bool => $value->value()['amount'] > 100,
        static fn (Variant $value): string => 'large payment',
    )
    ->caseWhen(
        'Paid',
        null,
        static fn (Variant $value): string => 'other paid amount',
    )
    ->run();
```

`caseWhen(string|array $keys, ?callable $guard, callable $handler)` registers a guarded handler. For the same key, handlers are tried in registration order; the first whose guard returns `true` is selected. A `null` guard is unconditional, making it useful as a per-key fallback. Registering the same key again appends a handler rather than replacing earlier handlers.

## Defaults and Exhaustiveness

```php
$matcher = PatternMatch::on(
    $payment,
    exhaustive: ['Paid', 'Pending', 'Failed'],
);

$result = $matcher
    ->case('Paid', static fn (Variant $value): string => 'paid')
    ->case('Pending', static fn (Variant $value): string => 'pending')
    ->case('Failed', static fn (Variant $value): string => 'failed')
    ->run();
```

`exhaustive(list<string> $keys)` declares the full set of expected keys. `run()` checks that each expected key has at least one registered handler and throws `LogicException` if any are missing. It does not verify that the subject's key is among the expected keys; an unmatched subject still causes `run()` to throw `MatchException` when there is no default handler. A registered `default()` skips the exhaustiveness check. `otherwise()` is an alias for `default()`.

## Running a Match

```php
$unhandledPayment = Variant::unit('Refunded');

$nullable = PatternMatch::on($unhandledPayment)->runOrNull();
$fallback = PatternMatch::on($unhandledPayment)->runOrElse('fallback');
$lazyFallback = PatternMatch::on($unhandledPayment)->runOrElseGet(
    static fn (): string => 'fallback',
);
```

- `run()` executes the match and throws `MatchException` if no case or default applies. It also performs the exhaustiveness check.
- `runOrNull()` returns `null` if no case or default applies; it does not check exhaustiveness.
- `runOrElse($default)` returns the supplied value if no case or default applies.
- `runOrElseGet($default)` evaluates the callback only if no case or default applies.

All four methods execute the matcher. After execution, it is frozen: calling `case()`, `caseWhen()`, `default()`, `otherwise()`, or `exhaustive()` on that matcher throws `LogicException`.

## How Keys Match

`Matcher` checks each registered key as follows:

1. If the subject implements `Adt`, its `matchKey()` must be strictly equal to the registered key.
2. Otherwise, if the key is a class or interface name, the subject must be an instance of it.
3. Other keys do not match.

For a class-based ADT, match using its class name:

```php
use Exact\Pattern\PatternMatch;
use Exact\Type\Option\None;
use Exact\Type\Option\Some;
use Exact\Type\Option\Option;

$description = PatternMatch::on(Option::some('Ada'))
    ->case(Some::class, static fn (Some $value): string => 'present')
    ->case(None::class, static fn (None $value): string => 'absent')
    ->run();
```

Implement `Adt` when a class should be matched by a semantic key instead of its class name:

```php
use Exact\Pattern\Adt;

final readonly class OrderState implements Adt
{
    public function __construct(private string $state)
    {
    }

    public function matchKey(): string
    {
        return $this->state;
    }
}

$description = PatternMatch::on(new OrderState('Shipped'))
    ->case('Shipped', static fn (OrderState $state): string => 'on the way')
    ->run();
```

`Variant` implements `Adt`, using its name as the match key. The class-based `Some`, `None`, `Left`, and `Right` types do not implement `Adt`, so the matcher dispatches them by class name.

## Method Reference

### `Variant`

| Method | Description |
|---|---|
| `of(string $name, mixed $value = null)` | Create a tagged value with an optional payload. |
| `unit(string $name)` | Create a tagged value with a `null` payload. |
| `name()` | Return the case name. |
| `value()` | Return the payload. |
| `is(string $name)` | Check the case name. |
| `matchKey()` | Return the key used by `Matcher` (same as `name()`). |
| `equals(self $other)` | Compare name and payload using strict equality. |
| `jsonSerialize()` | Serialize to `['name' => ..., 'value' => ...]`. |
| `__toString()` | Render the case name and, when present, a payload representation. |

### `PatternMatch`

| Method | Description |
|---|---|
| `on(object $subject, ?array $exhaustive = null)` | Create a matcher, optionally declaring expected keys. |

### `Matcher`

| Method | Description |
|---|---|
| `__construct(object $subject)` | Construct a matcher directly. |
| <code>case(string&#124;array $keys, callable $handler)</code> | Register an unconditional handler for one or more keys. |
| <code>caseWhen(string&#124;array $keys, ?callable $guard, callable $handler)</code> | Register a guarded handler; handlers for a key are tried in order. |
| `default(callable $handler)` | Register a fallback handler. |
| `otherwise(callable $handler)` | Alias for `default()`. |
| `exhaustive(list<string> $keys)` | Declare expected keys; enforced by `run()` when no default is registered. |
| `run()` | Execute; throw `MatchException` if nothing matches. |
| `runOrNull()` | Execute; return `null` if nothing matches. |
| `runOrElse(mixed $default)` | Execute; return a value if nothing matches. |
| `runOrElseGet(callable $default)` | Execute; evaluate a fallback lazily if nothing matches. |

### `Adt`

| Method | Description |
|---|---|
| `matchKey(): string` | Return the semantic identifier used to match the subject. |

## Next Steps

- See [Pattern Matching](../patterns/pattern-matching.md) for state workflows.
- Combine a variant with [Either](../fundamentals/either.md) in [Payment](../examples/payment.md).
