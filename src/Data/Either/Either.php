<?php

declare(strict_types=1);

namespace Exact\Data\Either;

use LogicException;
use TypeError;

final class Either
{
    private function __construct(
        private readonly bool $isLeft,
        private readonly mixed $value,
    ) {
    }

    public static function left(mixed $value): self
    {
        return new self(true, $value);
    }

    public static function right(mixed $value): self
    {
        return new self(false, $value);
    }

    public function isLeft(): bool
    {
        return $this->isLeft;
    }

    public function isRight(): bool
    {
        return !$this->isLeft;
    }

    /**
     * Transform the Right value.
     *
     * Left is propagated unchanged.
     */
    public function map(callable $fn): self
    {
        if ($this->isLeft) {
            return $this;
        }

        return self::right($fn($this->value));
    }

    /**
     * Transform the Left value.
     *
     * Right is propagated unchanged.
     */
    public function mapLeft(callable $fn): self
    {
        if ($this->isRight()) {
            return $this;
        }

        return self::left($fn($this->value));
    }

    /**
     * Chain an operation returning Either.
     */
    public function flatMap(callable $fn): self
    {
        if ($this->isLeft) {
            return $this;
        }

        $result = $fn($this->value);

        if (!$result instanceof self) {
            throw new TypeError(
                'Either::flatMap() callback must return an Either.'
            );
        }

        return $result;
    }

    /**
     * Handle both branches and produce a final value.
     */
    public function fold(
        callable $onLeft,
        callable $onRight,
    ): mixed {
        return $this->isLeft
            ? $onLeft($this->value)
            : $onRight($this->value);
    }

    /**
     * Return the Right value or a fallback.
     */
    public function getOrElse(mixed $default): mixed
    {
        return $this->isRight()
            ? $this->value
            : $default;
    }

    /**
     * Return the Left value.
     *
     * @throws LogicException when this Either is Right.
     */
    public function getLeft(): mixed
    {
        if ($this->isRight()) {
            throw new LogicException(
                'Cannot get Left value from a Right.'
            );
        }

        return $this->value;
    }

    /**
     * Return the Right value.
     *
     * @throws LogicException when this Either is Left.
     */
    public function getRight(): mixed
    {
        if ($this->isLeft) {
            throw new LogicException(
                'Cannot get Right value from a Left.'
            );
        }

        return $this->value;
    }

    /**
     * Return the contained value regardless of the branch.
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * Transform Left into Right and Right into Left.
     */
    public function swap(): self
    {
        return $this->isLeft
            ? self::right($this->value)
            : self::left($this->value);
    }
}