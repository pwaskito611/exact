<?php

declare(strict_types=1);

namespace Exact\Type\Either;

use Exact\Type\Option\Option;
use LogicException;
use Throwable;
use TypeError;

/**
 * Right-biased result type for explicit domain computation.
 *
 * @template L
 * @template R
 */
final class Either
{
    private function __construct(
        private readonly bool $isLeft,
        private readonly mixed $value,
    ) {
    }

    /**
     * @template L
     * @template R
     * @param L $value
     * @return self<L, R>
     */
    public static function left(mixed $value): self
    {
        return new self(true, $value);
    }

    /**
     * @template L
     * @template R
     * @param R $value
     * @return self<L, R>
     */
    public static function right(mixed $value): self
    {
        return new self(false, $value);
    }

    /**
     * Explicit alias for constructing a Right value.
     *
     * @template L
     * @template R
     * @param R $value
     * @return self<L, R>
     */
    public static function of(mixed $value): self
    {
        return self::right($value);
    }

    /**
     * Monadic/applicative convenience constructor for a Right value.
     *
     * @template L
     * @template R
     * @param R $value
     * @return self<L, R>
     */
    public static function pure(mixed $value): self
    {
        return self::right($value);
    }

    /**
     * Create a nullable value as Right or Left.
     *
     * @template L
     * @template R
     * @param R|null $value
     * @param L $left
     * @return self<L, R>
     */
    public static function fromNullable(mixed $value, mixed $left = null): self
    {
        return $value === null
            ? self::left($left)
            : self::right($value);
    }

    /**
     * Choose Right or Left based on a condition.
     *
     * @template L
     * @template R
     * @param R $right
     * @param L $left
     * @return self<L, R>
     */
    public static function cond(bool $condition, mixed $right, mixed $left): self
    {
        return $condition
            ? self::right($right)
            : self::left($left);
    }

    /**
     * Safely execute a callback and convert thrown exceptions into Left values.
     *
     * @template L
     * @template R
     * @param callable(): R $fn
     * @param (callable(Throwable): L)|null $onError
     * @return self<L, R>
     */
    public static function tryCatch(callable $fn, ?callable $onError = null): self
    {
        try {
            return self::right($fn());
        } catch (Throwable $exception) {
            if ($onError !== null) {
                return self::left($onError($exception));
            }

            return self::left($exception);
        }
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
     *
     * @template T
     * @param callable(R): T $fn
     * @return self<L, T>
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
     *
     * @template T
     * @param callable(L): T $fn
     * @return self<T, R>
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
     *
     * @template T
     * @param callable(R): self<L, T> $fn
     * @return self<L, T>
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
     * Apply a transformation to either branch, but only the matching one.
     *
     * @template L2
     * @template R2
     * @param callable(L): L2 $onLeft
     * @param callable(R): R2 $onRight
     * @return self<L2, R2>
     */
    public function bimap(callable $onLeft, callable $onRight): self
    {
        if ($this->isLeft) {
            return self::left($onLeft($this->value));
        }

        return self::right($onRight($this->value));
    }

    /**
     * Chain Left-side recovery.
     *
     * @template T
     * @param callable(L): self<T, R> $fn
     * @return self<T, R>
     */
    public function flatMapLeft(callable $fn): self
    {
        if ($this->isRight()) {
            return $this;
        }

        $result = $fn($this->value);

        if (!$result instanceof self) {
            throw new TypeError(
                'Either::flatMapLeft() callback must return an Either.'
            );
        }

        return $result;
    }

    /**
     * Handle both branches and produce a final value.
     *
     * @template T
     * @param callable(L): T $onLeft
     * @param callable(R): T $onRight
     * @return T
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
     *
     * @template T
     * @param T $default
     * @return R|T
     */
    public function getOrElse(mixed $default): mixed
    {
        return $this->isRight()
            ? $this->value
            : $default;
    }

    /**
     * Return the Right value or evaluate a lazy default.
     *
     * @template T
     * @param callable(): T $default
     * @return R|T
     */
    public function getOrElseGet(callable $default): mixed
    {
        return $this->isRight()
            ? $this->value
            : $default();
    }

    /**
     * Return the Right value or throw a supplied exception.
     *
     * @param Throwable|callable(): Throwable $onLeft
     * @return R
     * @throws Throwable
     */
    public function getOrElseThrow(Throwable|callable $onLeft): mixed
    {
        if ($this->isRight()) {
            return $this->value;
        }

        if ($onLeft instanceof Throwable) {
            throw $onLeft;
        }

        $exception = $onLeft();

        if (!$exception instanceof Throwable) {
            throw new TypeError(
                'Either::getOrElseThrow() callable must return a Throwable.'
            );
        }

        throw $exception;
    }

    /**
     * Return the Right value or null.
     *
     * @return R|null
     */
    public function getOrNull(): mixed
    {
        return $this->isRight() ? $this->value : null;
    }

    /**
     * Return the Left value or null.
     *
     * @return L|null
     */
    public function getLeftOrNull(): mixed
    {
        return $this->isLeft ? $this->value : null;
    }

    /**
     * Return the current instance on Right, otherwise use the alternative.
     *
     * @template L2
     * @param self<L2, R> $alternative
     * @return self<L2, R>
     */
    public function orElse(self $alternative): self
    {
        return $this->isRight()
            ? $this
            : $alternative;
    }

    /**
     * Return the current instance on Right, otherwise evaluate the alternative.
     *
     * @template L2
     * @param callable(): self<L2, R> $alternative
     * @return self<L2, R>
     */
    public function orElseGet(callable $alternative): self
    {
        if ($this->isRight()) {
            return $this;
        }

        $result = $alternative();

        if (!$result instanceof self) {
            throw new TypeError(
                'Either::orElseGet() callback must return an Either.'
            );
        }

        return $result;
    }

    /**
     * Keep the Right value only when the predicate matches.
     *
     * Left is propagated unchanged; $leftValue is used only when this is
     * Right and the predicate fails.
     *
     * @param callable(R): bool $predicate
     * @param L $leftValue
     * @return self<L, R>
     */
    public function filter(callable $predicate, mixed $leftValue = null): self
    {
        if ($this->isLeft) {
            return $this;
        }

        return $predicate($this->value)
            ? $this
            : self::left($leftValue);
    }

    /**
     * Check whether the Right value satisfies a predicate.
     *
     * @param callable(R): bool $predicate
     */
    public function exists(callable $predicate): bool
    {
        return $this->isRight() && $predicate($this->value);
    }

    /**
     * Check a predicate across all possible Right values.
     *
     * @param callable(R): bool $predicate
     */
    public function forall(callable $predicate): bool
    {
        return !$this->isRight() || $predicate($this->value);
    }

    /**
     * Check whether the Right value is strictly equal to the provided value.
     *
     * @param R $value
     */
    public function contains(mixed $value): bool
    {
        return $this->isRight() && $this->value === $value;
    }

    /**
     * Compare two Either values by branch and contained value.
     *
     * @param self<L, R> $other
     */
    public function equals(self $other): bool
    {
        if ($this->isLeft !== $other->isLeft) {
            return false;
        }

        return $this->value === $other->value;
    }

    /**
     * Execute an effect for a Right value without altering it.
     *
     * @param callable(R): mixed $fn
     * @return self<L, R>
     */
    public function tap(callable $fn): self
    {
        if ($this->isRight()) {
            $fn($this->value);
        }

        return $this;
    }

    /**
     * Execute an effect for a Left value without altering it.
     *
     * @param callable(L): mixed $fn
     * @return self<L, R>
     */
    public function tapLeft(callable $fn): self
    {
        if ($this->isLeft) {
            $fn($this->value);
        }

        return $this;
    }

    /**
     * Apply this Either (containing a callable) to another Either.
     *
     * @template T
     * @param self<L, R> $either
     * @return self<L, T>
     */
    public function ap(self $either): self
    {
        if ($this->isLeft) {
            return $this;
        }

        if ($either->isLeft()) {
            return $either;
        }

        $fn = $this->value;

        if (!is_callable($fn)) {
            throw new TypeError(
                'Either::ap() requires the current Right value to be callable.'
            );
        }

        return self::right($fn($either->value));
    }

    /**
     * Combine two Either values with a function when both are Right.
     *
     * @template T
     * @template U
     * @param self<L, T> $other
     * @param callable(R, T): U $fn
     * @return self<L, U>
     */
    public function zipWith(self $other, callable $fn): self
    {
        if ($this->isLeft) {
            return $this;
        }

        if ($other->isLeft()) {
            return $other;
        }

        return self::right($fn($this->value, $other->value));
    }

    /**
     * Pair the current Right value with another Right value.
     *
     * @template T
     * @param self<L, T> $other
     * @return self<L, array{0: R, 1: T}>
     */
    public function zip(self $other): self
    {
        return $this->zipWith(
            $other,
            fn (mixed $left, mixed $right): array => [$left, $right],
        );
    }

    /**
     * Flatten a nested Either instance.
     *
     * @return self<L, R>
     */
    public function flatten(): self
    {
        if ($this->isLeft) {
            return $this;
        }

        if (!$this->value instanceof self) {
            throw new TypeError(
                'Either::flatten() requires a nested Either value.'
            );
        }

        return $this->value;
    }

    /**
     * Accumulate Either values in order while failing fast on the first Left.
     *
     * @template T
     * @param iterable<self<L, T>> $eithers
     * @return self<L, list<T>>
     */
    public static function sequence(iterable $eithers): self
    {
        $results = [];

        foreach ($eithers as $either) {
            if (!$either instanceof self) {
                throw new TypeError(
                    'Either::sequence() requires every item to be an Either.'
                );
            }

            if ($either->isLeft()) {
                return $either;
            }

            $results[] = $either->value;
        }

        return self::right($results);
    }

    /**
     * Traverse a list of items and accumulate successful results.
     *
     * @template T
     * @template U
     * @param iterable<T> $items
     * @param callable(T): self<L, U> $fn
     * @return self<L, list<U>>
     */
    public static function traverse(iterable $items, callable $fn): self
    {
        $results = [];

        foreach ($items as $item) {
            $either = $fn($item);

            if (!$either instanceof self) {
                throw new TypeError(
                    'Either::traverse() callback must return an Either.'
                );
            }

            if ($either->isLeft()) {
                return $either;
            }

            $results[] = $either->value;
        }

        return self::right($results);
    }

    /**
     * Return the Left value.
     *
     * @return L
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
     * @return R
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
     *
     * @warning This discards Left/Right information. Prefer fold(),
     *          getOrElse(), or getOrElseGet() in domain code.
     *
     * @return L|R
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * Explicit unsafe/raw extraction alias for domain code that intentionally
     * needs the payload without branch checks.
     *
     * @return L|R
     */
    public function unsafeGet(): mixed
    {
        return $this->get();
    }

    /**
     * Convert this Either into an Option by dropping Left information.
     *
     * @return Option<R>
     */
    public function toOption(): Option
    {
        return $this->isRight()
            ? Option::some($this->value)
            : Option::none();
    }

    /**
     * Transform Left into Right and Right into Left.
     *
     * @return self<R, L>
     */
    public function swap(): self
    {
        return $this->isLeft
            ? self::right($this->value)
            : self::left($this->value);
    }
}