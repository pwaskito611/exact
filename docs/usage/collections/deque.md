# Deque

`Deque` is an immutable ordered collection with operations at both ends. `pushFront()` / `pushBack()` and their aliases `prepend()` / `append()` return new values.

## Quick Example

```php
use Exact\Collection\Deque;

$queue = Deque::of('second', 'third')
    ->pushFront('first')
    ->pushBack('fourth');

$first = $queue->peekFront();
$withoutFirst = $queue->popFront();
$withoutLast = $queue->popBack();
```

`peekFront()` and `peekBack()` inspect without removing. `popFront()` and `popBack()` return the remaining `Deque`; they do not return a `[value, collection]` pair. Use `peekFrontOrNull()` / `peekBackOrNull()` when an empty deque is allowed, since strict peek and pop operations throw `OutOfBoundsException` on empty input.

## When to Use Deque

Use `Deque` when a workflow needs efficient appends at the back and occasional edits at the front, without giving up immutability. Use [PriorityQueue](priority-queue.md) when the next item is selected by priority. See [Collections Overview](overview.md) for other collection choices.

The implementation uses a PHP list: front operations copy and shift the list, so this is an immutable convenience structure rather than a specialized high-throughput linked deque.

## Complexity

Because `Deque` is backed by a single list:

- `pushBack()` / `append()` — O(1) amortized.
- `popBack()` — O(1) amortized.
- `pushFront()` / `prepend()` — O(n) (copies and shifts).
- `popFront()` — O(n).
- `peekFront()`, `peekBack()`, `count()`, `isEmpty()` — O(1).

If a workflow needs heavy front operations, wrap a mutable structure such as `SplDoublyLinkedList` yourself; `Deque` trades that performance for immutability.

## Creating a Deque

```php
use Exact\Collection\Deque;

$empty = Deque::empty();
$fromValues = Deque::of('first', 'second', 'third');
$fromArray = Deque::fromArray(['first', 'second', 'third']);
$fromIterable = Deque::fromIterable(new ArrayIterator(['first', 'second']));
```

- `Deque::empty()` creates an empty deque.
- `Deque::of(...$items)` creates a deque from the given values in order.
- `Deque::fromArray(array $items)` creates a deque from a list; keys are discarded.
- `Deque::fromIterable(iterable $items)` accepts any iterable; arrays avoid the intermediate iterator conversion.

All factories normalize their input to a PHP list, so indexes are always `0..n-1`.

## Adding at Either End

```php
$queue = Deque::of('second');

$prefixed = $queue->pushFront('first');   // ['first', 'second']
$suffixed = $queue->pushBack('third');    // ['second', 'third']

$alsoPrefixed = $queue->prepend('first'); // alias for pushFront()
$alsoSuffixed = $queue->append('third');  // alias for pushBack()
```

- `pushFront($value)` / `prepend($value)` add to the front and return a new `Deque`.
- `pushBack($value)` / `append($value)` add to the back and return a new `Deque`.
- Both accept mixed values; the type is inferred from the existing entries in docblocks, but nothing is enforced at runtime.

## Removing from Either End

```php
$queue = Deque::of('first', 'second', 'third');

$withoutFirst = $queue->popFront(); // ['second', 'third']
$withoutLast = $queue->popBack();   // ['first', 'second']
```

- `popFront()` returns the deque without its first item.
- `popBack()` returns the deque without its last item.
- Both throw `OutOfBoundsException` on an empty deque.

Neither method returns the removed value. To read it first, use `peekFront()` / `peekBack()`:

```php
$queue = Deque::of('first', 'second');

$value = $queue->peekFront();       // 'first'
$rest = $queue->popFront();         // ['second']
```

## Peeking Without Removing

```php
$queue = Deque::of('first', 'second', 'third');

$front = $queue->peekFront();       // 'first'
$back = $queue->peekBack();         // 'third'

$frontMaybe = $queue->peekFrontOrNull();
$backMaybe = $queue->peekBackOrNull();

$alsoFirst = $queue->first();       // alias for peekFront()
$alsoLast = $queue->last();         // alias for peekBack()
```

- `peekFront()` / `first()` return the first item; throws `OutOfBoundsException` on empty.
- `peekBack()` / `last()` return the last item; throws `OutOfBoundsException` on empty.
- `peekFrontOrNull()` / `peekBackOrNull()` return `null` on empty instead of throwing.

The `OrNull` variants are the right choice when an empty deque is a normal state rather than an error.

## Inspecting Size and Contents

```php
$queue = Deque::of('first', 'second', 'third');

$count = $queue->count();      // 3
$size = $queue->size();        // 3, alias for count()
$isEmpty = $queue->isEmpty();  // false

$second = $queue->at(1);       // 'second'
```

- `count()` / `size()` return the number of items.
- `isEmpty()` reports whether the deque has no items.
- `at($index)` returns the item at a zero-based index; throws `OutOfBoundsException` if the index is negative or `>= count()`.

Unlike `peekFront()` / `peekBack()`, `at()` allows access to any position. It is O(1) because the deque is a plain list.

## Transforming a Deque

```php
$queue = Deque::of(1, 2, 3);

$doubled = $queue->map(static fn (int $value): int => $value * 2);
$evens = $queue->map(static fn (int $value): int => $value * 2)
    ->filter(static fn (int $value): bool => $value % 4 === 0);
$reversed = $queue->reverse();
```

- `map(callable $fn)` returns a new `Deque` with `$fn` applied to each item.
- `filter(callable $predicate)` keeps items for which `$predicate` returns `true`, re-indexing the result.
- `reverse()` returns a new `Deque` with the order reversed.

Both `map()` and `filter()` preserve the deque abstraction; they never return a raw array.

## Running Side Effects

```php
$queue = Deque::of('first', 'second');

$same = $queue->tap(static function (string $value): void {
    error_log("Inspecting: {$value}");
});
```

`tap(callable $fn)` runs `$fn` on every item for side effects and returns the same deque instance unchanged. It does not build a new deque.

## Comparing Deques

```php
$a = Deque::of(1, 2, 3);
$b = Deque::of(1, 2, 3);
$c = Deque::of(3, 2, 1);

$a->equals($b); // true
$a->equals($c); // false
```

`equals(self $other)` compares the underlying lists with strict equality, so both values and their order must match.

## Interop

```php
$queue = Deque::of('first', 'second', 'third');

$array = $queue->toArray(); // ['first', 'second', 'third']

foreach ($queue as $index => $value) {
    // iterate in order, $index is 0..n-1
}

$json = json_encode($queue); // ["first","second","third"]
```

- `toArray()` returns the items as a plain list.
- `getIterator()` yields items with integer indexes starting at 0.
- `jsonSerialize()` produces a JSON array, not an object.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `empty` | `static empty(): self` | Create an empty deque. |
| `of` | `static of(mixed ...$items): self` | Create a deque from the given values. |
| `fromArray` | `static fromArray(array $items): self` | Create a deque from a list; keys are discarded. |
| `fromIterable` | `static fromIterable(iterable $items): self` | Create a deque from any iterable. |
| `pushFront` | `pushFront(mixed $value): self` | Add to the front. |
| `pushBack` | `pushBack(mixed $value): self` | Add to the back. |
| `prepend` | `prepend(mixed $value): self` | Alias for `pushFront()`. |
| `append` | `append(mixed $value): self` | Alias for `pushBack()`. |
| `popFront` | `popFront(): self` | Remove the first item; throws `OutOfBoundsException` on empty. |
| `popBack` | `popBack(): self` | Remove the last item; throws `OutOfBoundsException` on empty. |
| `peekFront` | `peekFront(): mixed` | Return the first item; throws `OutOfBoundsException` on empty. |
| `peekBack` | `peekBack(): mixed` | Return the last item; throws `OutOfBoundsException` on empty. |
| `peekFrontOrNull` | `peekFrontOrNull(): mixed` | Return the first item or `null`. |
| `peekBackOrNull` | `peekBackOrNull(): mixed` | Return the last item or `null`. |
| `first` | `first(): mixed` | Alias for `peekFront()`. |
| `last` | `last(): mixed` | Alias for `peekBack()`. |
| `isEmpty` | `isEmpty(): bool` | Report whether the deque has no items. |
| `count` | `count(): int` | Return the number of items. |
| `size` | `size(): int` | Alias for `count()`. |
| `at` | `at(int $index): mixed` | Return the item at a zero-based index; throws `OutOfBoundsException`. |
| `map` | `map(callable $fn): self` | Transform each item into a new deque. |
| `filter` | `filter(callable $predicate): self` | Keep items that satisfy a predicate. |
| `reverse` | `reverse(): self` | Return a deque with the order reversed. |
| `tap` | `tap(callable $fn): self` | Run a side effect for each item; returns the same instance. |
| `equals` | `equals(self $other): bool` | Compare items and their order. |
| `toArray` | `toArray(): array` | Return items as a list. |
| `getIterator` | `getIterator(): Traversable` | Iterate items in order. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize items as a JSON array. |

## Next Steps

- Use [PriorityQueue](priority-queue.md) when the next item is selected by priority.
- See [Collections Overview](overview.md) for other collection choices.