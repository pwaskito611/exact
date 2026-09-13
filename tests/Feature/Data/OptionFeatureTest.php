<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Data;

use Exact\Data\Option\Option;
use PHPUnit\Framework\TestCase;

final class OptionFeatureTest extends TestCase
{
    public function test_it_normalizes_and_filters_an_optional_email(): void
    {
        $email = Option::from(' USER@EXAMPLE.COM ')
            ->map(static fn (string $value): string => strtolower(trim($value)))
            ->filter(static fn (string $value): bool => str_contains($value, '@'))
            ->getOrElse('noreply@example.com');

        self::assertSame('user@example.com', $email);
    }

    public function test_it_uses_a_fallback_when_optional_input_is_absent(): void
    {
        $email = Option::from(null)
            ->map(static fn (string $value): string => strtolower($value))
            ->getOrElse('noreply@example.com');

        self::assertSame('noreply@example.com', $email);
    }
}