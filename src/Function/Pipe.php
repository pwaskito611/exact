<?php

declare(strict_types=1);

namespace Exact\Function;

final class Pipe
{
    public static function of(mixed $value): self
    {
        return new self($value);
    }

    private function __construct(
        private readonly mixed $value,
    ) {
    }

    public function through(callable ...$functions): self
    {
        $value = $this->value;

        foreach ($functions as $function) {
            $value = $function($value);
        }

        return new self($value);
    }

    public function map(callable $fn): self
    {
        return new self(
            $fn($this->value),
        );
    }

    public function get(): mixed
    {
        return $this->value;
    }
}