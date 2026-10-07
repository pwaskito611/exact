<?php

declare(strict_types=1);

namespace Exact\Pattern;

use JsonSerializable;

/**
 * Dynamic tagged value: a name plus an optional payload.
 *
 * Use Variant when the set of cases is not known at compile time — for
 * example, values decoded from JSON, plugin-defined states, or user-driven
 * workflows. For statically known ADTs prefer real classes (Some, None,
 * Left, Right) so the type system can reason about them.
 *
 * @template TValue
 * @implements JsonSerializable<array{name: string, value: TValue}>
 */
final readonly class Variant implements Adt, JsonSerializable
{
    /**
     * @param TValue $value
     */
    private function __construct(
        private string $name,
        private mixed $value,
    ) {
    }

    /**
     * Tagged value with a payload.
     *
     * @template U
     * @param U $value
     * @return self<U>
     */
    public static function of(string $name, mixed $value = null): self
    {
        return new self($name, $value);
    }

    /**
     * Tagged value without payload (an enum-like case).
     *
     * @return self<null>
     */
    public static function unit(string $name): self
    {
        return new self($name, null);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return TValue
     */
    public function value(): mixed
    {
        return $this->value;
    }

    public function is(string $name): bool
    {
        return $this->name === $name;
    }

    public function matchKey(): string
    {
        return $this->name;
    }

    /**
     * @param self<mixed> $other
     */
    public function equals(self $other): bool
    {
        return $this->name === $other->name
            && $this->value === $other->value;
    }

    /**
     * @return array{name: string, value: TValue}
     */
    public function jsonSerialize(): array
    {
        return ['name' => $this->name, 'value' => $this->value];
    }

    public function __toString(): string
    {
        if ($this->value === null) {
            return $this->name;
        }

        $repr = is_scalar($this->value)
            ? var_export($this->value, true)
            : get_debug_type($this->value);

        return "{$this->name}({$repr})";
    }
}