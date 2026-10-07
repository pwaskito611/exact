# Pattern Matching

Pattern matching turns a named state into a value by selecting a handler for its key. `Matcher` accepts any object as its subject. Keys can be semantic values matched through `Adt::matchKey()` (such as `Variant`), or class and interface names matched with `instanceof` when the subject does not implement `Adt`.

## Basic Workflow

```php
use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;

$state = Variant::of('Pending', ['orderId' => 'O-10']);

$label = PatternMatch::on($state)
    ->case(
        'Pending',
        static fn (Variant $value): string => "Waiting for {$value->value()['orderId']}",
    )
    ->case('Paid', static fn (Variant $value): string => 'Paid')
    ->default(static fn (Variant $value): string => "State: {$value->name()}")
    ->run();
```

Every handler receives the subject itself, so type-hint `Variant` (or the concrete class) and read the payload through `name()` and `value()`. Registered cases are tried before the default handler.

`PatternMatch::on()` is the normal entry point. `Matcher` also has a public constructor:

```php
use Exact\Pattern\Matcher;
use Exact\Pattern\Variant;

$label = (new Matcher(Variant::of('Paid', 'R-204')))
    ->case('Paid', static fn (Variant $value): string => "Paid: {$value->value()}")
    ->run();
```

## Registering Cases

```php
use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;

$payment = Variant::unit('Paid');

$result = PatternMatch::on($payment)
    ->case('Paid', static fn (Variant $value): string => 'receipt')
    ->case(['Pending', 'Processing'], static fn (Variant $value): string => 'in progress')
    ->run();
```

`case(string|array $keys, callable $handler)` registers an unconditional handler for one key or a list of keys. Registering the same key again appends a handler rather than replacing earlier handlers. Handlers for that key are evaluated in registration order.

### Guarded Cases

```php
$payment = Variant::of('Paid', ['amount' => 150]);

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

`caseWhen(string|array $keys, ?callable $guard, callable $handler)` registers a guarded handler. For the same key, handlers are tried in registration order; the first whose guard returns `true` wins. A `null` guard is unconditional and is useful as a per-key fallback.

## Defaults and Exhaustiveness

```php
use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;

$payment = Variant::unit('Paid');

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

`exhaustive(list<string> $keys)` declares the expected keys. `run()` checks that each expected key has at least one registered handler and throws `LogicException` if any are missing. It does not verify that the subject's key is among the expected keys; without a matching case or default, `run()` throws `MatchException`. A registered `default()` skips the exhaustiveness check.

Calling `default()` again replaces the previous fallback. `otherwise(callable $handler)` is an alias for `default()`:

```php
$matcher = PatternMatch::on($payment, exhaustive: ['Paid', 'Pending'])
    ->otherwise(static fn (Variant $value): string => 'fallback');
```

## Running a Match

```php
$unhandled = Variant::unit('Refunded');

$nullable = PatternMatch::on($unhandled)->runOrNull();
$fallback = PatternMatch::on($unhandled)->runOrElse('fallback');
$lazyFallback = PatternMatch::on($unhandled)->runOrElseGet(
    static fn (): string => 'fallback',
);
```

- `run()` executes the match, performs the exhaustiveness check, and throws `MatchException` if nothing matches and no default is registered.
- `runOrNull()` returns `null` if no case or default applies; it does not check exhaustiveness.
- `runOrElse(mixed $default)` returns the given value if no case or default applies.
- `runOrElseGet(callable $default)` evaluates its callback only if no case or default applies.

Each method marks the matcher as executed. After execution, calling `case()`, `caseWhen()`, `default()`, `otherwise()`, or `exhaustive()` on that matcher throws `LogicException`.

## Returning Other Exact Types

Handlers can return `Either`, `Option`, or a domain value, making matching one step in a larger workflow:

```php
use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;
use Exact\Type\Either\Either;

$result = PatternMatch::on(Variant::of('Failed', 'DECLINED'))
    ->case('Paid', static fn (Variant $value): Either => Either::right($value->value()))
    ->case('Failed', static fn (Variant $value): Either => Either::left($value->value()))
    ->run();
```

## How Keys Match

`Matcher` checks whether a key applies to its subject as follows:

1. If the subject implements `Adt`, its `matchKey()` must be strictly equal to the registered key.
2. Otherwise, if the key is a class or interface name, the subject must be an instance of it.
3. Other keys do not match.

For class-based ADTs, match by class name:

```php
use Exact\Pattern\PatternMatch;
use Exact\Type\Option\None;
use Exact\Type\Option\Option;
use Exact\Type\Option\Some;

$description = PatternMatch::on(Option::some('Ada'))
    ->case(Some::class, static fn (Some $value): string => 'present')
    ->case(None::class, static fn (None $value): string => 'absent')
    ->run();
```

Implement `Adt` when a class should be matched by a semantic key instead of its class name:

```php
use Exact\Pattern\Adt;
use Exact\Pattern\PatternMatch;

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

`Variant` implements `Adt`, using its name as the match key. `Some`, `None`, `Left`, and `Right` do not implement `Adt`, so the matcher dispatches them by class name.

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
| <code>caseWhen(string&#124;array $keys, ?callable $guard, callable $handler)</code> | Register a guarded handler; handlers for a key are tried in registration order. |
| `default(callable $handler)` | Register or replace the fallback handler. |
| `otherwise(callable $handler)` | Alias for `default()`. |
| `exhaustive(list<string> $keys)` | Declare expected keys; enforced by `run()` when no default is registered. |
| `run()` | Execute; throw `MatchException` if nothing matches. |
| `runOrNull()` | Execute; return `null` if nothing matches. |
| `runOrElse(mixed $default)` | Execute; return the fallback if nothing matches. |
| `runOrElseGet(callable $default)` | Execute; evaluate a fallback lazily if nothing matches. |

### `Adt`

| Method | Description |
|---|---|
| `matchKey(): string` | Return the semantic identifier used to match the subject. |

## Next Steps

- See [ADT](../modeling/adt.md) for the `Variant` type itself.
- Combine a variant with [Either](../fundamentals/either.md) in [Payment](../examples/payment.md).
