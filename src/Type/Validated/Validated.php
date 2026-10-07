<?php

declare(strict_types=1);

namespace Exact\Type\Validated;

use Exact\Type\Either\Either;
use Exact\Type\Option\Option;
use JsonSerializable;
use LogicException;
use Throwable;
use TypeError;

/**
 * Applicative validation type.
 *
 * Unlike Either, Validated accumulates errors instead of short-circuiting.
 * Use Validated for input validation where every problem should be reported
 * at once. Use Either for business pipelines where the first error is enough.
 *
 * Validated is applicative but not a monad: andThen() is fail-fast and
 * does not accumulate across continuations.
 *
 * @template E
 * @template T
 * @implements JsonSerializable<mixed>
 */
abstract readonly class Validated implements JsonSerializable
{
    /**
     * @param T|E $value
     */
    public function __construct(
        protected mixed $value,
    ) {
    }

    // ───────────────────────── Constructors ─────────────────────────

    /**
     * @template U
     * @param U $value
     * @return self<never, U>
     */
    public static function valid(mixed $value): self
    {
        return new Valid($value);
    }

    /**
     * @template F
     * @param F $error
     * @return self<F, never>
     */
    public static function invalid(mixed $error): self
    {
        return new Invalid($error);
    }

    /**
     * Alias for valid().
     *
     * @template U
     * @param U $value
     * @return self<never, U>
     */
    public static function of(mixed $value): self
    {
        return self::valid($value);
    }

    /**
     * Alias for valid().
     *
     * @template U
     * @param U $value
     * @return self<never, U>
     */
    public static function pure(mixed $value): self
    {
        return self::valid($value);
    }

    /**
     * @template F
     * @template U
     * @param U $valid
     * @param F $invalid
     * @return self<F, U>
     */
    public static function cond(bool $condition, mixed $valid, mixed $invalid): self
    {
        return $condition
            ? self::valid($valid)
            : self::invalid($invalid);
    }

    /**
     * @template F
     * @template U
     * @param callable(): U $fn
     * @param callable(Throwable): F $onError
     * @return self<F, U>
     */
    public static function tryCatch(callable $fn, callable $onError): self
    {
        try {
            return self::valid($fn());
        } catch (Throwable $e) {
            return self::invalid($onError($e));
        }
    }

    // ───────────────────────── Query ─────────────────────────

    abstract public function isValid(): bool;

    public function isInvalid(): bool
    {
        return !$this->isValid();
    }

    // ───────────────────────── Mapping ─────────────────────────

    /**
     * @template U
     * @param callable(T): U $fn
     * @return self<E, U>
     */
    public function map(callable $fn): self
    {
        return $this->isValid()
            ? self::valid($fn($this->value))
            : $this;
    }

    /**
     * @template F
     * @param callable(E): F $fn
     * @return self<F, T>
     */
    public function mapInvalid(callable $fn): self
    {
        return $this->isInvalid()
            ? self::invalid($fn($this->value))
            : $this;
    }

    /**
     * @template F
     * @template U
     * @param callable(E): F $onInvalid
     * @param callable(T): U $onValid
     * @return self<F, U>
     */
    public function bimap(callable $onInvalid, callable $onValid): self
    {
        return $this->isValid()
            ? self::valid($onValid($this->value))
            : self::invalid($onInvalid($this->value));
    }

    // ───────────────────────── Chaining ─────────────────────────

    /**
     * Fail-fast continuation.
     *
     * If Valid, call $fn and return its result.
     * If Invalid, return $this unchanged.
     *
     * Named andThen (not flatMap) because Validated is not a monad —
     * it does not accumulate errors across continuations.
     *
     * @template U
     * @param callable(T): self<E, U> $fn
     * @return self<E, U>
     */
    public function andThen(callable $fn): self
    {
        if ($this->isInvalid()) {
            return $this;
        }

        $result = $fn($this->value);

        if (!$result instanceof self) {
            throw new TypeError(
                'Validated::andThen() callback must return Validated.'
            );
        }

        return $result;
    }

    // ───────────────────────── Folding ─────────────────────────

    /**
     * @template U
     * @param callable(E): U $onInvalid
     * @param callable(T): U $onValid
     * @return U
     */
    public function fold(callable $onInvalid, callable $onValid): mixed
    {
        return $this->isValid()
            ? $onValid($this->value)
            : $onInvalid($this->value);
    }

    // ───────────────────────── Extraction ─────────────────────────

    /**
     * @template U
     * @param U $default
     * @return T|U
     */
    public function getOrElse(mixed $default): mixed
    {
        return $this->isValid() ? $this->value : $default;
    }

    /**
     * @template U
     * @param callable(): U $default
     * @return T|U
     */
    public function getOrElseGet(callable $default): mixed
    {
        return $this->isValid() ? $this->value : $default();
    }

    /**
     * @param Throwable|callable(E): Throwable $onInvalid
     * @return T
     * @throws Throwable
     */
    public function getOrElseThrow(Throwable|callable $onInvalid): mixed
    {
        if ($this->isValid()) {
            return $this->value;
        }

        if ($onInvalid instanceof Throwable) {
            throw $onInvalid;
        }

        $exception = $onInvalid($this->value);

        if (!$exception instanceof Throwable) {
            throw new TypeError(
                'Validated::getOrElseThrow() callable must return a Throwable.'
            );
        }

        throw $exception;
    }

    /**
     * @return T|null
     */
    public function getOrNull(): mixed
    {
        return $this->isValid() ? $this->value : null;
    }

    /**
     * @return E|null
     */
    public function getInvalidOrNull(): mixed
    {
        return $this->isInvalid() ? $this->value : null;
    }

    /**
     * @return T
     * @throws LogicException
     */
    public function getValid(): mixed
    {
        if ($this->isInvalid()) {
            throw new LogicException(
                'Cannot get Valid value from an Invalid.'
            );
        }

        return $this->value;
    }

    /**
     * @return E
     * @throws LogicException
     */
    public function getInvalid(): mixed
    {
        if ($this->isValid()) {
            throw new LogicException(
                'Cannot get Invalid value from a Valid.'
            );
        }

        return $this->value;
    }

    /**
     * Return the contained value regardless of the branch.
     *
     * @warning This discards Valid/Invalid information. Prefer fold(),
     *          getOrElse(), or getValid()/getInvalid() in domain code.
     *
     * @return T|E
     */
    public function unsafeGet(): mixed
    {
        return $this->value;
    }

    // ───────────────────────── Alternatives ─────────────────────────

    /**
     * @param self<E, T> $alternative
     * @return self<E, T>
     */
    public function orElse(self $alternative): self
    {
        return $this->isValid() ? $this : $alternative;
    }

    /**
     * @param callable(): self<E, T> $alternative
     * @return self<E, T>
     */
    public function orElseGet(callable $alternative): self
    {
        if ($this->isValid()) {
            return $this;
        }

        $result = $alternative();

        if (!$result instanceof self) {
            throw new TypeError(
                'Validated::orElseGet() callback must return Validated.'
            );
        }

        return $result;
    }

    // ───────────────────────── Predicates ─────────────────────────

    /**
     * Convert to Invalid when the predicate fails.
     *
     * @param callable(T): bool $predicate
     * @param E $error
     * @return self<E, T>
     */
    public function filter(callable $predicate, mixed $error): self
    {
        if ($this->isInvalid()) {
            return $this;
        }

        return $predicate($this->value)
            ? $this
            : self::invalid($error);
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function exists(callable $predicate): bool
    {
        return $this->isValid() && $predicate($this->value);
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function forall(callable $predicate): bool
    {
        return $this->isInvalid() || $predicate($this->value);
    }

    /**
     * @param T $value
     */
    public function contains(mixed $value): bool
    {
        return $this->isValid() && $this->value === $value;
    }

    /**
     * @param self<E, T> $other
     */
    public function equals(self $other): bool
    {
        if ($this->isValid() !== $other->isValid()) {
            return false;
        }

        return $this->value === $other->value;
    }

    // ───────────────────────── Effects ─────────────────────────

    /**
     * @param callable(T): mixed $fn
     * @return self<E, T>
     */
    public function tap(callable $fn): self
    {
        if ($this->isValid()) {
            $fn($this->value);
        }

        return $this;
    }

    /**
     * @param callable(E): mixed $fn
     * @return self<E, T>
     */
    public function tapInvalid(callable $fn): self
    {
        if ($this->isInvalid()) {
            $fn($this->value);
        }

        return $this;
    }

    // ───────────────────────── Applicative ─────────────────────────

    /**
     * Apply this Validated (containing a callable) to another Validated.
     * When both are Invalid, combine via $combineError.
     *
     * @template U
     * @param self<E, U> $other
     * @param callable(E, E): E $combineError
     * @return self<E, mixed>
     */
    public function ap(self $other, callable $combineError): self
    {
        if ($this->isValid() && $other->isValid()) {
            $fn = $this->value;

            if (!is_callable($fn)) {
                throw new TypeError(
                    'Validated::ap() requires a callable in the Valid position.'
                );
            }

            return self::valid($fn($other->value));
        }

        if ($this->isInvalid() && $other->isInvalid()) {
            return self::invalid(
                $combineError($this->value, $other->value)
            );
        }

        return $this->isInvalid() ? $this : $other;
    }

    /**
     * Combine two Validated values into a pair.
     * When both are Invalid, combine via $combineError.
     *
     * @template U
     * @param self<E, U> $other
     * @param callable(E, E): E $combineError
     * @return self<E, array{0: T, 1: U}>
     */
    public function combine(self $other, callable $combineError): self
    {
        if ($this->isValid() && $other->isValid()) {
            return self::valid([$this->value, $other->value]);
        }

        if ($this->isInvalid() && $other->isInvalid()) {
            return self::invalid(
                $combineError($this->value, $other->value)
            );
        }

        return $this->isInvalid() ? $this : $other;
    }

    /**
     * @template U
     * @template V
     * @param self<E, U> $other
     * @param callable(T, U): V $fn
     * @param callable(E, E): E $combineError
     * @return self<E, V>
     */
    public function zipWith(
        self $other,
        callable $fn,
        callable $combineError,
    ): self {
        return $this->combine($other, $combineError)
            ->map(fn (array $pair) => $fn($pair[0], $pair[1]));
    }

    /**
     * @template U
     * @param self<E, U> $other
     * @param callable(E, E): E $combineError
     * @return self<E, array{0: T, 1: U}>
     */
    public function zip(self $other, callable $combineError): self
    {
        return $this->combine($other, $combineError);
    }

    // ───────────────────────── Aggregation ─────────────────────────

    /**
     * Accumulate many Validated values into one.
     *
     * If all are Valid → Valid(list of values).
     * If any are Invalid → Invalid(combined errors).
     *
     * @template F
     * @template U
     * @param iterable<self<F, U>> $validateds
     * @param callable(list<F>): F $combineErrors
     * @return self<F, list<U>>
     */
    public static function sequence(
        iterable $validateds,
        callable $combineErrors,
    ): self {
        $values = [];
        $errors = [];

        foreach ($validateds as $v) {
            if (!$v instanceof self) {
                throw new TypeError(
                    'Validated::sequence() requires every item to be Validated.'
                );
            }

            if ($v->isValid()) {
                $values[] = $v->value;
            } else {
                $errors[] = $v->value;
            }
        }

        if ($errors !== []) {
            return self::invalid($combineErrors($errors));
        }

        return self::valid($values);
    }

    /**
     * @template F
     * @template U
     * @template V
     * @param iterable<U> $items
     * @param callable(U): self<F, V> $fn
     * @param callable(list<F>): F $combineErrors
     * @return self<F, list<V>>
     */
    public static function traverse(
        iterable $items,
        callable $fn,
        callable $combineErrors,
    ): self {
        $validateds = [];

        foreach ($items as $item) {
            $validateds[] = $fn($item);
        }

        return self::sequence($validateds, $combineErrors);
    }

    /**
     * Split a collection into errors and values.
     *
     * @template F
     * @template U
     * @param iterable<self<F, U>> $validateds
     * @return array{0: list<F>, 1: list<U>}
     */
    public static function partition(iterable $validateds): array
    {
        $errors = [];
        $values = [];

        foreach ($validateds as $v) {
            if (!$v instanceof self) {
                throw new TypeError(
                    'Validated::partition() requires every item to be Validated.'
                );
            }

            if ($v->isValid()) {
                $values[] = $v->value;
            } else {
                $errors[] = $v->value;
            }
        }

        return [$errors, $values];
    }

    /**
     * Return the first Valid in the collection, or Invalid with all errors.
     *
     * @template F
     * @template U
     * @param iterable<self<F, U>> $validateds
     * @param callable(list<F>): F $combineErrors
     * @return self<F, U>
     */
    public static function firstValid(
        iterable $validateds,
        callable $combineErrors,
    ): self {
        $errors = [];

        foreach ($validateds as $v) {
            if (!$v instanceof self) {
                throw new TypeError(
                    'Validated::firstValid() requires every item to be Validated.'
                );
            }

            if ($v->isValid()) {
                return $v;
            }

            $errors[] = $v->value;
        }

        return self::invalid($combineErrors($errors));
    }

    // ───────────────────────── Interop ─────────────────────────

    /**
     * @return Either<E, T>
     */
    public function toEither(): Either
    {
        return $this->isValid()
            ? Either::right($this->value)
            : Either::left($this->value);
    }

    /**
     * @return Option<T>
     */
    public function toOption(): Option
    {
        return $this->isValid()
            ? Option::some($this->value)
            : Option::none();
    }

    // ───────────────────────── Utility ─────────────────────────

    /**
     * Swap the Valid and Invalid branches.
     *
     * @return self<T, E>
     */
    public function swap(): self
    {
        return $this->isValid()
            ? self::invalid($this->value)
            : self::valid($this->value);
    }

    /**
     * @return array{valid: true, value: T}|array{valid: false, errors: E}
     */
    public function jsonSerialize(): array
    {
        return $this->isValid()
            ? ['valid' => true, 'value' => $this->value]
            : ['valid' => false, 'errors' => $this->value];
    }
}