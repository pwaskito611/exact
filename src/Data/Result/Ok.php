<?php

declare(strict_types=1);

namespace Exact\Data\Result;

use LogicException;

final readonly class Ok extends Result
{
    public function __construct(
        private mixed $value,
    ) {
    }

    public function isOk(): bool
    {
        return true;
    }

    public function map(callable $fn): self
    {
        return new self(
            $fn($this->value),
        );
    }

    public function mapErr(callable $fn): self
    {
        return $this;
    }

    public function flatMap(callable $fn): Result
    {
        $result = $fn($this->value);

        if (!$result instanceof Result) {
            throw new \TypeError(
                'Result::flatMap() callback must return Result.'
            );
        }

        return $result;
    }

    public function fold(
        callable $onErr,
        callable $onOk,
    ): mixed {
        return $onOk($this->value);
    }

    public function getOrElse(mixed $default): mixed
    {
        return $this->value;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function error(): never
    {
        throw new LogicException(
            'Cannot get error from Ok.'
        );
    }
}