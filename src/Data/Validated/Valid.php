<?php

declare(strict_types=1);

namespace Exact\Data\Validated;

final class Valid extends Validated
{
    public function isValid(): bool
    {
        return true;
    }
}