<?php

declare(strict_types=1);

namespace Exact\Data\Result;

use LogicException;

final readonly class Err extends Result
{
    public function __construct(
        private mixed $error,
    ) {
    }

    public function isOk(): bool
    {
        return false;
    }

    public function map(callable $fn): self
    {
        return $this;
    }

    public function mapErr(callable $fn): self
    {
        return new self(
            $fn($this->error),
        );
    }

    public function flatMap(callable $fn): self
    {
        return $this;
    }

    public function fold(
        callable $onErr,
        callable $onOk,
    ): mixed {
        return $onErr($this->error);
    }

    public function getOrElse(mixed $default): mixed
    {
        return $default;
    }

    public function get(): never
    {
        throw new LogicException(
            'Cannot get value from Err.'
        );
    }

    public function error(): mixed
    {
        return $this->error;
    }
}