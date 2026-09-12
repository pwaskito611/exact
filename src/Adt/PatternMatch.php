<?php

declare(strict_types=1);

namespace Exact\Adt;

final class PatternMatch
{
    public static function on(Variant $variant): Matcher
    {
        return new Matcher($variant);
    }
}