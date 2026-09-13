# Value Objects

`ValueObject` is a base abstraction for domain values whose identity is defined by their contents rather than by object identity.

## When to Use It

Use a value object when a combination of values has domain meaning and should define its own equality rule, such as money, an address, or a date range.

## Basic Usage

```php
use Exact\Value\ValueObject;

final readonly class Money extends ValueObject
{
    public function __construct(
        private int $amount,
        private string $currency,
    ) {
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->amount === $other->amount
            && $this->currency === $other->currency;
    }
}

$samePrice = (new Money(100, 'USD'))->equals(new Money(100, 'USD'));
```

The subclass owns the equality rule. `ValueObject` requires `equals()` to accept another `ValueObject`.

## Next Steps

- Use [Newtypes](newtypes.md) when a value needs one distinct type and one wrapped value.
- Combine domain values with [Result](../fundamentals/result.md) in [Domain Modeling](../patterns/domain-modeling.md).