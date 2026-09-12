<?php

declare(strict_types=1);

namespace Exact\Data\Tuple;

final class Pair
{
    private function __construct(
        private readonly mixed $first,
        private readonly mixed $second,
    ) {
    }

    public static function of(mixed $first, mixed $second): self
    {
        return new self($first, $second);
    }

    public function first(): mixed
    {
        return $this->first;
    }

    public function second(): mixed
    {
        return $this->second;
    }

    public function swap(): self
    {
        return new self($this->second, $this->first);
    }

    public function toArray(): array
    {
        return [$this->first, $this->second];
    }
}
