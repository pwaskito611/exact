<?php

declare(strict_types=1);

namespace Exact\Adt;

use Exact\Exception\MatchException;

final class Matcher
{
    /** @var array<string, callable> */
    private array $cases = [];

    /** @var callable|null */
    private mixed $defaultHandler = null;

    public function __construct(
        private readonly Variant $variant,
    ) {
    }

    public function case(
        string $name,
        callable $handler,
    ): self {
        $this->cases[$name] = $handler;

        return $this;
    }

    public function default(callable $handler): self
    {
        $this->defaultHandler = $handler;

        return $this;
    }

    public function run(): mixed
    {
        $name = $this->variant->name();

        if (isset($this->cases[$name])) {
            return ($this->cases[$name])(
                $this->variant->value(),
            );
        }

        if ($this->defaultHandler !== null) {
            return ($this->defaultHandler)(
                $this->variant->value(),
                $name,
            );
        }

        throw MatchException::noMatch($this->variant);
    }
}