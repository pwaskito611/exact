<?php

declare(strict_types=1);

namespace Exact\Collection;

final readonly class Map
{
    /**
     * @param array<array-key, mixed> $items
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
        if (count($items) % 2 !== 0) {
            throw new \InvalidArgumentException(
                'Map::of() requires key-value pairs.'
            );
        }

        $map = [];

        for ($i = 0; $i < count($items); $i += 2) {
            $map[$items[$i]] = $items[$i + 1];
        }

        return new self($map);
    }

    /**
     * @param array<array-key, mixed> $items
     */
    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    public function has(string|int $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function get(string|int $key): mixed
    {
        return $this->items[$key] ?? null;
    }

    public function getOrElse(
        string|int $key,
        mixed $default,
    ): mixed {
        return $this->items[$key] ?? $default;
    }

    public function put(
        string|int $key,
        mixed $value,
    ): self {
        return new self([
            ...$this->items,
            $key => $value,
        ]);
    }

    public function remove(string|int $key): self
    {
        $items = $this->items;
        unset($items[$key]);

        return new self($items);
    }

    public function size(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function map(callable $fn): self
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[$key] = $fn($value, $key);
        }

        return new self($result);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }
}