<?php

declare(strict_types=1);

namespace Exact\Data\Tuple;

use OutOfBoundsException;

final class Tuple
{
    /**
     * @param array<int, mixed> $values
     */
    private function __construct(
        private readonly array $values,
    ) {
    }

    public static function of(mixed ...$values): self
    {
        return new self($values);
    }

    public function at(int $index): mixed
    {
        if (!array_key_exists($index, $this->values)) {
            throw new OutOfBoundsException('Index out of range');
        }

        return $this->values[$index];
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function map(callable $fn): self
    {
        return new self(array_map($fn, $this->values));
    }

    public function toArray(): array
    {
        return $this->values;
    }
}
