# Newtypes

`Newtype` gives a single wrapped value a distinct domain type, so an identifier cannot be confused with an arbitrary string.

## Define a Domain Type

```php
use Exact\Value\Newtype;

final readonly class UserId extends Newtype
{
}

$id = new UserId('user-42');

$rawValue = $id->value();
$sameId = $id->equals(new UserId('user-42'));
```

Extend the abstract base with a `readonly` class. The inherited constructor is public; `value()` returns the wrapped value.

## Compare Domain Values

`equals()` accepts another `Newtype` and returns `true` only when the runtime classes match and their wrapped values are strictly identical. It does not coerce scalar types.

```php
final readonly class OrderId extends Newtype
{
}

$sameUser = $id->equals(new UserId('user-42'));
$sameTextDifferentType = $id->equals(new OrderId('user-42'));
```

The second comparison is `false`, even though both wrappers contain the same string.

## When to Use a Newtype

Use it for identifiers, codes, or another single value whose domain meaning should be visible in the type. Use a [Value Object](value-objects.md) when equality depends on several fields or custom rules.

## When to Use It

Use a newtype for identifiers, codes, or other single values where accepting any string or integer would make a domain boundary unclear. Use a [Value Object](value-objects.md) for a value made from several fields or with custom comparison behavior.

## Next Steps

- See [Registration](../examples/registration.md) for a newtype at a domain boundary.
- Continue to [Domain Modeling](../patterns/domain-modeling.md).