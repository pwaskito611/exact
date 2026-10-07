<?php

declare(strict_types=1);

namespace Exact\Pattern;

/**
 * Contract for values that can be matched by a semantic key.
 *
 * Class-based ADTs (Some, None, Left, Right) do not need to implement this;
 * Matcher falls back to the class name via ::class. Implement Adt when you
 * need a custom key — for example, a Variant whose name is meaningful.
 */
interface Adt
{
    /**
     * Identifier used by Matcher when the key is not a class-string.
     */
    public function matchKey(): string;
}