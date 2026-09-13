# Newtypes

`Newtype` wraps one value in a distinct class. It is useful when two values have the same PHP scalar type but different domain meanings.

## Basic Usage

```php
use Exact\Value\Newtype;

final readonly class UserId extends Newtype
{
}

$id = new UserId('user-42');

$rawValue = $id->value();
$sameId = $id->equals(new UserId('user-42'));
```

`equals()` requires the same subclass and a strictly equal wrapped value. A `UserId` is therefore not equal to another `Newtype` subclass containing the same string.

## When to Use It

Use a newtype for identifiers, codes, or other single values where accepting any string or integer would make a domain boundary unclear. Use a [Value Object](value-objects.md) for a value made from several fields or with custom comparison behavior.

## Next Steps

- See [Registration](../examples/registration.md) for a newtype at a domain boundary.
- Continue to [Domain Modeling](../patterns/domain-modeling.md).