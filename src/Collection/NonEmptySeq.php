<?php

declare(strict_types=1);

namespace Exact\Collection;

final readonly class NonEmptySeq
{
    /**
     * @param array<int, mixed> $items
     */
    private function __construct(
        private array $items,
    ) {
        if ($items === []) {
            throw new \InvalidArgumentException(
                'NonEmptyList cannot be empty.'
            );
        }
    }

    public static function of(mixed $head, mixed ...$tail): self
    {
        return new self([
            $head,
            ...$tail,
        ]);
    }

    /**
     * @param array<int, mixed> $items
     */
    public static function fromArray(array $items): self
    {
        return new self(array_values($items));
    }

    public function head(): mixed
    {
        return $this->items[0];
    }

    public function tail(): Seq
    {
        return Seq::fromArray(
            array_slice($this->items, 1)
        );
    }

    public function size(): int
    {
        return count($this->items);
    }

    public function map(callable $fn): self
    {
        return new self(
            array_map($fn, $this->items)
        );
    }

    public function filter(callable $predicate): Seq
    {
        return Seq::fromArray(
            array_values(
                array_filter($this->items, $predicate)
            )
        );
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
}