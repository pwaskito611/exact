<?php

declare(strict_types=1);

namespace Exact\Data\Result;

abstract readonly class Result
{
    public static function ok(mixed $value): self
    {
        return new Ok($value);
    }

    public static function err(mixed $error): self
    {
        return new Err($error);
    }

    abstract public function isOk(): bool;

    public function isErr(): bool
    {
        return !$this->isOk();
    }

    abstract public function map(callable $fn): self;

    abstract public function mapErr(callable $fn): self;

    abstract public function flatMap(callable $fn): self;

    abstract public function fold(
        callable $onErr,
        callable $onOk,
    ): mixed;

    abstract public function getOrElse(mixed $default): mixed;

    abstract public function get(): mixed;
}