<?php

declare(strict_types=1);

namespace Exact\Pattern;

/**
 * Entry point for pattern matching.
 *
 * @see Matcher
 */
final class PatternMatch
{
    /**
     * Begin a match on the given subject.
     *
     * @template T of object
     * @param T $subject
     * @param list<string>|null $exhaustive
     * @return Matcher<T>
     */
    public static function on(object $subject, ?array $exhaustive = null): Matcher
    {
        $matcher = new Matcher($subject);

        if ($exhaustive !== null) {
            $matcher->exhaustive($exhaustive);
        }

        return $matcher;
    }
}