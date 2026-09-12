<?php

declare(strict_types=1);

namespace Exact\Adt;

final readonly class Variant
{
    private function __construct(
        private string $name,
        private mixed $value,
    ) {
    }

    public static function of(
        string $name,
        mixed $value = null,
    ): self {
        return new self($name, $value);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function is(string $name): bool
    {
        return $this->name === $name;
    }
}