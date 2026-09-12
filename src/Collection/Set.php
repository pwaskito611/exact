<?php

declare(strict_types=1);

namespace Exact\Collection;

final readonly class Set
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
        $result = [];

        foreach ($items as $item) {
            if (!in_array($item, $result, true)) {
                $result[] = $item;
            }
        }

        return new self($result);
    }

    public function add(mixed $value): self
    {
        if ($this->contains($value)) {
            return $this;
        }

        return new self([
            ...$this->items,
            $value,
        ]);
    }

    public function remove(mixed $value): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->items,
                    fn (mixed $item) => $item !== $value,
                )
            )
        );
    }

    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function size(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function union(self $other): self
    {
        return self::of(
            ...$this->items,
            ...$other->items,
        );
    }

    public function intersect(self $other): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->items,
                    fn (mixed $item) => $other->contains($item),
                )
            )
        );
    }

    public function map(callable $fn): self
    {
        return self::of(
            ...array_map($fn, $this->items),
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }
}