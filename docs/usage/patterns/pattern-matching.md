# Pattern Matching

Pattern matching turns a named `Variant` into a value by selecting a handler for its name.

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