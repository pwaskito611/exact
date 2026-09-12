<?php

declare(strict_types=1);

namespace Exact\Collections;

final class ImmutableList
{
    /**
     * @param array<int, mixed> $items
     */
    public function __construct(
        private readonly array $items = [],
    ) {
    }

    /**
     * @param array<int, mixed> $items
     */
    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    /**
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    public function map(callable $fn): self
    {
        return new self(array_map($fn, $this->items));
    }

    public function filter(callable $predicate): self
    {
        return new self(array_values(array_filter($this->items, $predicate)));
    }

    public function append(mixed $value): self
    {
        return new self([...$this->items, $value]);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
