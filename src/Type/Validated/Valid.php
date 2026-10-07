<?php

declare(strict_types=1);

namespace Exact\Type\Validated;

/**
 * @template T
 * @extends Validated<never, T>
 */
final readonly class Valid extends Validated
{
    public function isValid(): bool
    {
        return true;
    }
}