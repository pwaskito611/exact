# Pattern Matching

Pattern matching turns a named `Variant` into a value by selecting a handler for its name.

`PatternMatch::on($variant)` is the normal entry point. It returns a `Matcher`; the matcher's public constructor also accepts a `Variant` directly. `case($name, $handler)` and `default($handler)` configure it, and `run()` performs dispatch and returns the selected handler's result.

## Basic Workflow

```php
use Exact\Adt\PatternMatch;
use Exact\Adt\Variant;

$state = Variant::of('Pending', ['orderId' => 'O-10']);

$label = PatternMatch::on($state)
    ->case(
        'Pending',
        static fn (array $value): string => "Waiting for {$value['orderId']}",
    )
    ->case('Paid', static fn (array $value): string => 'Paid')
    ->default(static fn (mixed $value, string $name): string => "State: {$name}")
    ->run();
```

The matching case wins over the default. A case handler receives the variant payload. A default handler receives the payload and name.

Registering the same case name again replaces its earlier handler. Likewise, calling `default()` again replaces the prior fallback. `Matcher::run()` throws `MatchException` when the active variant has no registered case and no default.

`Matcher` also has a public constructor that accepts a `Variant`. `PatternMatch::on()` is the shorter static entry point for the same workflow:

```php
use Exact\Adt\Matcher;
use Exact\Adt\Variant;

$label = (new Matcher(Variant::of('Paid', 'R-204')))
    ->case('Paid', static fn (string $receipt): string => "Paid: {$receipt}")
    ->run();
```

## Without a Default

Omit `default()` when an unhandled state should be visible as an error. `Matcher::run()` throws `MatchException` if no case matches.

## Returning Other Exact Types

Handlers can return `Result`, `Option`, or a domain value. This makes matching useful as one step in a larger workflow:

```php
use Exact\Data\Result\Result;

$result = PatternMatch::on(Variant::of('Failed', 'DECLINED'))
    ->case('Paid', static fn (string $receipt): Result => Result::ok($receipt))
    ->case('Failed', static fn (string $error): Result => Result::err($error))
    ->run();
```

See [ADT](../modeling/adt.md) and [Payment](../examples/payment.md).