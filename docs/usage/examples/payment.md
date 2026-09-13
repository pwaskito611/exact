# Payment

Payment states are a natural fit for named variants. Pattern matching can convert each state into a `Result` for the rest of the workflow.

```php
use Exact\Adt\PatternMatch;
use Exact\Adt\Variant;
use Exact\Data\Result\Result;

$payment = Variant::of('Failed', ['code' => 'DECLINED']);

$result = PatternMatch::on($payment)
    ->case(
        'Paid',
        static fn (array $receipt): Result => Result::ok($receipt['id']),
    )
    ->case(
        'Failed',
        static fn (array $failure): Result => Result::err($failure['code']),
    )
    ->default(static fn (): Result => Result::err('Unknown payment state'))
    ->run();

$message = $result->fold(
    static fn (mixed $error): string => "Payment failed: {$error}",
    static fn (string $receipt): string => "Payment receipt: {$receipt}",
);
```

The variant expresses the state; `Result` expresses the outcome of continuing with the payment workflow. These are related but distinct responsibilities.

## Next Steps

- Learn [Pattern Matching](../patterns/pattern-matching.md).
- Review [Result](../fundamentals/result.md) for sequential failure handling.