<?php

declare(strict_types=1);

namespace Exact\Type\Validated;

/**
 * @template E
 * @extends Validated<E, never>
 */
final readonly class Invalid extends Validated
{
    public function isValid(): bool
    {
        return false;
    }
}