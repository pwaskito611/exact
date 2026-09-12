<?php

declare(strict_types=1);

namespace Exact\Value;

abstract readonly class Newtype
{
    public function __construct(
        protected mixed $value,
    ) {}

    final public function value(): mixed
    {
        return $this->value;
    }

    final public function equals(Newtype $other): bool
    {
        return $this::class === $other::class
            && $this->value === $other->value;
    }
}