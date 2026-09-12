<?php

declare(strict_types=1);

namespace Exact\Collection;

use Exact\Exception\EmptyCollectionException;

final readonly class Seq
{
    /**
     * @param array<int, mixed> $items
     */
    private function __construct(
        private array $items,
    ) {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function of(mixed ...$items): self
    {
        return new self(array_values($items));
    }

    /**
     * @param array<int, mixed> $items
     */
    public static function fromArray(array $items): self
    {
        return new self(array_values($items));
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function size(): int
    {
        return count($this->items);
    }

    public function head(): mixed
    {
        if ($this->isEmpty()) {
            throw new EmptyCollectionException('Cannot get head of an empty list.');
        }

        return $this->items[0];
    }

    public function tail(): self
    {
        if ($this->isEmpty()) {
            throw new EmptyCollectionException('Cannot get tail of an empty list.');
        }

        return new self(array_slice($this->items, 1));
    }

    public function get(int $index): mixed
    {
        if (!array_key_exists($index, $this->items)) {
            throw new \OutOfBoundsException(
                "Index {$index} does not exist."
            );
        }

        return $this->items[$index];
    }

    public function map(callable $fn): self
    {
        return new self(
            array_map($fn, $this->items)
        );
    }

    public function filter(callable $predicate): self
    {
        return new self(
            array_values(
                array_filter($this->items, $predicate)
            )
        );
    }

    public function flatMap(callable $fn): self
    {
        $result = [];

        foreach ($this->items as $item) {
            $mapped = $fn($item);

            if (!$mapped instanceof self) {
                throw new \TypeError(
                    'List::flatMap() callback must return List.'
                );
            }

            foreach ($mapped->toArray() as $value) {
                $result[] = $value;
            }
        }

        return new self($result);
    }

    public function foldLeft(mixed $initial, callable $fn): mixed
    {
        $result = $initial;

        foreach ($this->items as $item) {
            $result = $fn($result, $item);
        }

        return $result;
    }

    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function append(mixed $value): self
    {
        return new self([
            ...$this->items,
            $value,
        ]);
    }

    public function prepend(mixed $value): self
    {
        return new self([
            $value,
            ...$this->items,
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @param callable(mixed): void $fn
     */
    public function each(callable $fn): void
    {
        foreach ($this->items as $item) {
            $fn($item);
        }
    }
}