# Value Objects

Use a value object when several fields together represent one domain value, such as a price with a currency.

## Define a Value Object

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
$differentCurrency = (new Money(100, 'USD'))->equals(new Money(100, 'EUR'));
```

`ValueObject` requires `equals(ValueObject $other): bool`, but does not provide storage, a constructor, or automatic equality. Each concrete class defines which fields determine equality.

In this example, equal amounts with different currencies are not equal. Keep the equality rule consistent with the fields that define the domain value.

## When to Use a Value Object

Use a value object when a type has multiple pieces of state or needs a domain-specific equality rule. Use a [Newtype](newtypes.md) for one wrapped value with a distinct meaning.

## Next Steps

- Use [Newtypes](newtypes.md) when a value needs one distinct type and one wrapped value.
- Combine domain values with [Result](../fundamentals/result.md) in [Domain Modeling](../patterns/domain-modeling.md).