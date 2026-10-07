# Validated

`Validated` represents a value that is either valid or invalid. Unlike [Either](either.md), combining invalid values can accumulate their errors through a callback. Use it for independent input checks where reporting multiple issues at once is useful.

## Creating and Checking a Validation

```php
use Exact\Type\Validated\Validated;

$name = Validated::valid('Ada');
$email = Validated::invalid('Email is required');

$isNameValid = $name->isValid();
$hasEmailError = $email->isInvalid();

$conditional = Validated::cond(
    $emailInput !== '',
    $emailInput,
    'Email is required',
);
$safeResult = Validated::tryCatch(
    static fn (): array => loadFormData(),
    static fn (Throwable $error): string => $error->getMessage(),
);
```

`valid()` and `invalid()` are the branch factories. `of()` and `pure()` are aliases for `valid()`. `cond()` chooses a branch from a boolean, and `tryCatch()` maps a thrown `Throwable` to `Invalid` using the required error callback. `isValid()` and `isInvalid()` return `bool`.

## Combining Independent Checks

```php
$profile = Validated::valid('Ada')->zipWith(
    Validated::valid('ada@example.com'),
    static fn (string $name, string $email): array => [
        'name' => $name,
        'email' => $email,
    ],
    static fn (mixed $firstError, mixed $secondError): array => [
        $firstError,
        $secondError,
    ],
);

$invalid = Validated::invalid('Name is required')->combine(
    Validated::invalid('Email is invalid'),
    static fn (mixed $firstError, mixed $secondError): array => [
        $firstError,
        $secondError,
    ],
);
```

`combine()` returns a `Valid` containing a two-item array when both inputs are valid. `zip()` is its alias. `zipWith()` combines two valid values using its first callback. These methods require an error-combining callback: it is called only when both inputs are invalid. If only one input is invalid, that `Invalid` is propagated unchanged. Error accumulation is defined by your callback; it is not automatic.

`ap()` applies a function stored in the receiver's `Valid` value to the other `Validated`. Like the combining methods, it requires a callback for the case where both inputs are invalid.

```php
$greeting = Validated::valid(
    static fn (string $name): string => "Hello, {$name}",
)->ap(
    Validated::valid('Ada'),
    static fn (mixed $firstError, mixed $secondError): array => [
        $firstError,
        $secondError,
    ],
);
```

## Transforming and Reading a Branch

`map()` transforms only a valid payload; `mapInvalid()` transforms only an error payload. `bimap()` takes both callbacks and applies only the one for the active branch:

```php
$normalizedName = Validated::valid(' Ada ')
    ->map(static fn (string $value): string => trim($value));

$normalizedIssue = Validated::invalid('missing email')
    ->mapInvalid(static fn (string $issue): string => strtoupper($issue));

$message = Validated::invalid('Email is required')->fold(
    static fn (string $issue): string => "Cannot register: {$issue}",
    static fn (string $value): string => "Registering {$value}",
);
```

`getValid()` and `getInvalid()` extract a known branch and throw `LogicException` for the opposite branch. `getOrElse()` returns the valid payload or a fallback; `getOrElseGet()` evaluates a fallback callback only for `Invalid`, and `getOrElseThrow()` returns the valid value or throws a supplied exception (or one created from the invalid payload). `getOrNull()` and `getInvalidOrNull()` are nullable branch-specific accessors. `unsafeGet()` returns the payload without checking its branch, so prefer a branch-aware method when possible.

```php
$displayName = Validated::valid('Ada')->getValid();
$fallbackName = Validated::invalid('Name is required')->getOrElse('Guest');
$rawPayload = Validated::invalid('Email is required')->unsafeGet();
```

There is no `get()` method on `Validated`; use `unsafeGet()` only when intentionally discarding branch information.

## Sequential Work and Recovery

`andThen()` runs a callback only for `Valid` and expects another `Validated`; `Invalid` is returned unchanged. Although it provides fail-fast continuation, it does not accumulate errors across steps, so use [Either](either.md) for sequential computations where short-circuiting is the intended model.

```php
$checkedEmail = Validated::valid('ada@example.com')->andThen(
    static fn (string $email): Validated => str_contains($email, '@')
        ? Validated::valid($email)
        : Validated::invalid('Email is invalid'),
);

$recovered = Validated::invalid('cached value unavailable')
    ->orElseGet(static fn (): Validated => Validated::valid('fallback'));
```

`filter()` converts a `Valid` to `Invalid($error)` when its predicate fails. `exists()`, `forall()`, `contains()`, and `equals()` inspect or compare the valid branch. `tap()` and `tapInvalid()` run side effects on the matching branch without changing the validation.

```php
$positiveId = Validated::valid(42)->filter(
    static fn (int $id): bool => $id > 0,
    'Identifier must be positive',
);

$checkedName = Validated::valid('Ada')->tap(
    static function (string $name): void {
        error_log("Validated name: {$name}");
    },
);
```

## Aggregating Validations

```php
$allFields = Validated::sequence(
    [
        Validated::valid('Ada'),
        Validated::valid('ada@example.com'),
    ],
    static fn (array $errors): array => $errors,
);

$checkedIds = Validated::traverse(
    [1, 2, 3],
    static fn (int $id): Validated => $id > 0
        ? Validated::valid($id)
        : Validated::invalid("Invalid id: {$id}"),
    static fn (array $errors): array => $errors,
);

[$errors, $values] = Validated::partition([
    Validated::invalid('Missing name'),
    Validated::valid('Ada'),
]);

$firstValid = Validated::firstValid(
    [
        Validated::invalid('Primary source failed'),
        Validated::valid('Backup value'),
    ],
    static fn (array $errors): array => $errors,
);
```

`sequence()` and `traverse()` accumulate every invalid payload into a list and pass that list to the required error-combining callback; if there are no errors, they return a list of valid values. `partition()` returns a two-item array in the order `[errors, values]`. `firstValid()` returns the first valid item or combines all collected invalid payloads if none is valid.

## Interop and Method Reference

```php
$either = Validated::invalid('Email is required')->toEither();
$option = Validated::valid('Ada')->toOption();
$swapped = Validated::valid('Ada')->swap();
$json = json_encode(Validated::invalid(['email' => 'Required']));
```

`toEither()` preserves the branch as `Left` or `Right`. `toOption()` keeps valid values as `Some` and discards invalid payloads as `None`. `swap()` exchanges the `Valid` and `Invalid` branches. JSON serialization uses `['valid' => true, 'value' => ...]` or `['valid' => false, 'errors' => ...]`.

| Method | Description |
|---|---|
| `valid()`, `invalid()`, `of()`, `pure()`, `cond()`, `tryCatch()` | Construct a branch from a value, condition, or callback. |
| `isValid()`, `isInvalid()` | Inspect the active branch. |
| `map()`, `mapInvalid()`, `bimap()` | Transform one or both branches. |
| `andThen()` | Continue from `Valid` with fail-fast behavior. |
| `fold()` | Handle both branches and return a value. |
| `getOrElse()`, `getOrElseGet()`, `getOrElseThrow()` | Extract a valid value or use a fallback. |
| `getOrNull()`, `getInvalidOrNull()`, `getValid()`, `getInvalid()`, `unsafeGet()` | Read a payload, either branch-aware or explicitly unchecked. |
| `orElse()`, `orElseGet()` | Provide an alternative when invalid. |
| `filter()`, `exists()`, `forall()`, `contains()`, `equals()` | Inspect or compare valid values. |
| `tap()`, `tapInvalid()` | Run a side effect on one branch. |
| `ap()`, `combine()`, `zip()`, `zipWith()` | Combine validations, with explicit invalid-payload accumulation. |
| `sequence()`, `traverse()`, `partition()`, `firstValid()` | Aggregate or select from multiple validations. |
| `toEither()`, `toOption()`, `swap()`, `jsonSerialize()` | Convert to another representation or exchange branches. |

## Next Steps

- See [Registration](../examples/registration.md) for an end-to-end input-validation flow.
- Compare Exact's outcome types in [Error Handling](../patterns/error-handling.md).
