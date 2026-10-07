# Payment

Payment states are a natural fit for named variants. Pattern matching can convert each state into an `Either` for the rest of the workflow.

```php
use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;
use Exact\Type\Either\Either;

$payment = Variant::of('Failed', ['code' => 'DECLINED']);

$result = PatternMatch::on($payment)
    ->case(
        'Paid',
        static fn (array $receipt): Either => Either::right($receipt['id']),
    )
    ->case(
        'Failed',
        static fn (array $failure): Either => Either::left($failure['code']),
    )
    ->default(static fn (mixed $payload, string $name): Either => Either::left("Unknown payment state: {$name}"))
    ->run();

$message = $result->fold(
    static fn (mixed $error): string => "Payment failed: {$error}",
    static fn (string $receipt): string => "Payment receipt: {$receipt}",
);
```

The variant expresses the state; `Either` expresses the outcome of continuing with the payment workflow. The `Left` and `Right` branches carry domain-specific meaning.

## Next Steps

- Learn [Pattern Matching](../patterns/pattern-matching.md).
- Review [Either](../fundamentals/either.md) for working with the resulting alternatives.