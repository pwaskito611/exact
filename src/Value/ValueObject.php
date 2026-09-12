<?php

declare(strict_types=1);

namespace Exact\Value;

abstract readonly class ValueObject
{
    /**
     * Determines whether this value object is equal to another value object.
     */
    abstract public function equals(self $other): bool;
}