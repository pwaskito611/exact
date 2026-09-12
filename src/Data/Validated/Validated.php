<?php

declare(strict_types=1);

namespace Exact\Data\Validated;

use LogicException;
use TypeError;

abstract class Validated
{
    protected function __construct(
        protected readonly mixed $value,
    ) {
    }

    public static function valid(mixed $value): self
    {
        return new Valid($value);
    }

    public static function invalid(mixed $error): self
    {
        return new Invalid($error);
    }

    abstract public function isValid(): bool;

    public function isInvalid(): bool
    {
        return !$this->isValid();
    }

    public function map(callable $fn): self
    {
        if ($this->isInvalid()) {
            return $this;
        }

        return self::valid($fn($this->value));
    }

    public function mapInvalid(callable $fn): self
    {
        if ($this->isValid()) {
            return $this;
        }

        return self::invalid($fn($this->value));
    }

    public function fold(
        callable $onInvalid,
        callable $onValid,
    ): mixed {
        return $this->isValid()
            ? $onValid($this->value)
            : $onInvalid($this->value);
    }

    public function getOrElse(mixed $default): mixed
    {
        return $this->isValid()
            ? $this->value
            : $default;
    }

    public function getValid(): mixed
    {
        if ($this->isInvalid()) {
            throw new LogicException(
                'Cannot get Valid value from an Invalid.'
            );
        }

        return $this->value;
    }

    public function getInvalid(): mixed
    {
        if ($this->isValid()) {
            throw new LogicException(
                'Cannot get Invalid value from a Valid.'
            );
        }

        return $this->value;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function combine(
        self $other,
        callable $combineValid,
        callable $combineInvalid,
    ): self {
        if ($this->isValid() && $other->isValid()) {
            return self::valid(
                $combineValid(
                    $this->value,
                    $other->value,
                )
            );
        }

        if ($this->isInvalid() && $other->isInvalid()) {
            return self::invalid(
                $combineInvalid(
                    $this->value,
                    $other->value,
                )
            );
        }

        if ($this->isInvalid()) {
            return self::invalid($this->value);
        }

        return self::invalid($other->value);
    }
}