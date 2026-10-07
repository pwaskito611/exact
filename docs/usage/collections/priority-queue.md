# PriorityQueue

`PriorityQueue` is an immutable binary heap. By default, the smallest value is at the head; pass a comparator to use another ordering.

## Quick Example

```php
use Exact\Collection\PriorityQueue;

$queue = PriorityQueue::fromArray([7, 2, 5]);

$next = $queue->peek(); // 2
[$value, $remaining] = $queue->pop();
// $value is 2; $remaining contains [5, 7] in priority order
```

`push()` returns a new queue. `pop()` returns a pair `[value, remainingQueue]`, leaving the original unchanged. `peek()` and `pop()` throw `OutOfBoundsException` when empty; `peekOrNull()` provides nullable inspection. `toSortedArray()` drains a copy and returns values from highest priority to lowest, without changing the queue. Iteration itself follows internal heap order, not sorted order.

For a max-heap, supply a comparator whose result is negative when the first argument should be removed earlier:

```php
$maxFirst = PriorityQueue::fromArray(
    [7, 2, 5],
    static fn (int $left, int $right): int => $right <=> $left,
);
```

## When to Use PriorityQueue

Use `PriorityQueue` when:

- You repeatedly need the smallest (or largest) item and the collection changes between reads.
- Insertion and removal both happen at the "priority end".
- Immutability matters — every operation returns a new queue.

Use [Deque](deque.md) when insertion and removal happen at both ends with no priority ordering.

## Comparator Semantics

The comparator returns an `int`, like `<=>`:

- Negative — the first argument should be removed **before** the second.
- Zero — the two arguments are considered equal in priority.
- Positive — the first argument should be removed **after** the second.

The default comparator is `fn ($a, $b) => $a <=> $b`, which makes this a **min-heap**: the smallest value is at the head.

To get a max-heap, reverse the comparison:

```php
$maxHeap = PriorityQueue::fromArray(
    [7, 2, 5],
    static fn (int $a, int $b): int => $b <=> $a,
);
$maxHeap->peek(); // 7
```

For non-scalar values, supply a comparator that compares the relevant field:

```php
final readonly class Task
{
    public function __construct(
        public string $name,
        public int $priority,
    ) {}
}

$tasks = PriorityQueue::fromArray(
    [
        new Task('email', 3),
        new Task('deploy', 1),
        new Task('meeting', 5),
    ],
    static fn (Task $a, Task $b): int => $a->priority <=> $b->priority,
);

$tasks->peek()->name; // 'deploy'
```

## Complexity

| Operation | Cost |
|---|---|
| `push()` | O(log n) |
| `pop()` | O(log n) |
| `peek()` / `peekOrNull()` | O(1) |
| `count()` / `size()` / `isEmpty()` | O(1) |
| `toSortedArray()` | O(n log n) |
| `fromArray()` / `fromIterable()` | O(n log n) — each item is pushed |

This is a classic binary heap. `fromArray()` does not use the O(n) heapify optimization; it pushes each element. For large inputs where performance matters, this detail may be worth measuring against a custom implementation.

## Creating a Queue

```php
use Exact\Collection\PriorityQueue;

$empty     = PriorityQueue::empty();
$emptyMax  = PriorityQueue::empty(static fn (int $a, int $b): int => $b <=> $a);
$fromArray = PriorityQueue::fromArray([7, 2, 5]);
$fromIter  = PriorityQueue::fromIterable(new ArrayIterator([7, 2, 5]));
```

- `PriorityQueue::empty(?callable $comparator = null)` creates an empty queue. When no comparator is provided, the default min-heap comparator is used.
- `PriorityQueue::fromArray(array $items, ?callable $comparator = null)` pushes each item.
- `PriorityQueue::fromIterable(iterable $items, ?callable $comparator = null)` accepts any iterable.

The comparator must be provided at construction time. It is stored on every new queue created by `push()` and `pop()`, so operations after construction preserve the ordering.

## Adding Values

```php
$queue = PriorityQueue::empty();

$queue = $queue->push(5)->push(2)->push(7);

$queue->peek(); // 2 — the smallest value is at the head
```

`push(mixed $value)` returns a new `PriorityQueue` with `$value` inserted. The heap invariant is restored by sifting the new value up. Because the queue is immutable, the original queue is unchanged.

`push()` cannot throw for value reasons — any value is accepted as long as the comparator can handle it.

## Removing Values

```php
$queue = PriorityQueue::fromArray([7, 2, 5]);

[$top, $rest] = $queue->pop();

$top;                 // 2
$rest->peek();        // 5
$queue->peek();       // 2 — the original queue is unchanged
```

`pop()` returns a two-element array `[value, remainingQueue]`, where `value` is the current head and `remainingQueue` is a new `PriorityQueue` without it. It throws `OutOfBoundsException` when the queue is empty.

Draining a queue in sorted order:

```php
$queue = PriorityQueue::fromArray([7, 2, 5]);
$sorted = [];

while (!$queue->isEmpty()) {
    [$value, $queue] = $queue->pop();
    $sorted[] = $value;
}
// $sorted === [2, 5, 7]
```

This is exactly what `toSortedArray()` does internally.

## Inspecting the Head

```php
$queue = PriorityQueue::fromArray([7, 2, 5]);

$top       = $queue->peek();        // 2
$topOrNull = $queue->peekOrNull();  // 2

$emptyPeek = PriorityQueue::empty()->peekOrNull(); // null
```

- `peek()` returns the highest-priority value; throws `OutOfBoundsException` when the queue is empty.
- `peekOrNull()` returns the value or `null`.

Neither method removes the value.

## Size and Emptiness

```php
$queue = PriorityQueue::fromArray([7, 2, 5]);

$count     = $queue->count();     // 3
$size      = $queue->size();      // 3, alias for count()
$isEmpty   = $queue->isEmpty();   // false
```

- `count()` / `size()` return the number of items.
- `isEmpty()` reports whether the queue has no items.

`PriorityQueue` implements `Countable`, so `count($queue)` (PHP's function) returns the same value as `$queue->count()`.

## Draining in Sorted Order

```php
$queue = PriorityQueue::fromArray([7, 2, 5]);

$sorted = $queue->toSortedArray(); // [2, 5, 7]
$queue->count();                   // still 3 — the original queue is unchanged
```

`toSortedArray()` returns a fresh list of all values in priority order (highest priority first). It does **not** modify the queue — it drains a copy internally.

This is the right method for materializing the queue as a sorted array. `toArray()` does **not** exist on `PriorityQueue`; if you need the internal heap representation, that is exposed only via `jsonSerialize()`.

## Iteration Order

```php
$queue = PriorityQueue::fromArray([7, 2, 5]);

foreach ($queue as $value) {
    // Iterates in internal heap order — NOT sorted.
}
```

`getIterator()` yields the internal heap array. This is **not** priority order and **not** insertion order. It is the binary heap layout used for O(1) `peek()` and O(log n) `push()` / `pop()`.

If you need sorted iteration, use `toSortedArray()`:

```php
foreach ($queue->toSortedArray() as $value) {
    // Iterates in priority order: 2, 5, 7
}
```

The internal order can change between operations, so do not rely on it for anything other than debugging.

## JSON Serialization

```php
$queue = PriorityQueue::fromArray([7, 2, 5]);

$json = json_encode($queue);
// e.g. "[2,7,5]" — the internal heap order, not sorted
```

`jsonSerialize()` returns the internal heap array. Like iteration, it reflects the internal representation, not sorted order. If you need a stable serialization, serialize `toSortedArray()` instead:

```php
json_encode($queue->toSortedArray()); // "[2,5,7]"
```

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `empty` | `static empty(?callable $comparator = null): self` | Create an empty queue. Uses the min-heap comparator when none is given. |
| `fromArray` | `static fromArray(array $items, ?callable $comparator = null): self` | Create from a list of items. |
| `fromIterable` | `static fromIterable(iterable $items, ?callable $comparator = null): self` | Create from any iterable. |
| `push` | `push(mixed $value): self` | Insert a value; returns a new queue. O(log n). |
| `pop` | `pop(): array` | Return `[value, remainingQueue]`; throws `OutOfBoundsException` when empty. |
| `peek` | `peek(): mixed` | Return the highest-priority value; throws `OutOfBoundsException` when empty. |
| `peekOrNull` | `peekOrNull(): mixed` | Return the highest-priority value or `null`. |
| `isEmpty` | `isEmpty(): bool` | Report whether the queue has no items. |
| `count` | `count(): int` | Number of items. |
| `size` | `size(): int` | Alias for `count()`. |
| `toSortedArray` | `toSortedArray(): array` | Return all values in priority order; leaves the queue unchanged. O(n log n). |
| `getIterator` | `getIterator(): Traversable` | Iterate in internal heap order — **not** sorted. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize the internal heap array — **not** sorted. |

## Next Steps

- Use [Deque](deque.md) when insertion and removal happen at both ends.
- Use [SortedSet](sorted-set.md) when values are unique and iteration must be ordered.
- See [Collections Overview](overview.md).