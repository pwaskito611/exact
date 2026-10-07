<?php

declare(strict_types=1);

namespace Exact\Tests\Integration\DomainModeling;

use Exact\Type\Either\Either;
use Exact\Type\Option\Option;
use PHPUnit\Framework\TestCase;

final class OptionRegistrationFlowTest extends TestCase
{
    public function test_it_uses_optional_profile_data_to_build_a_domain_record(): void
    {
        $email = Option::from(' ADA@EXAMPLE.COM ')
            ->map(static fn (string $value): string => strtolower(trim($value)))
            ->filter(static fn (string $value): bool => str_contains($value, '@'))
            ->toEither('email is required');

        $record = $email
            ->map(static fn (string $value): array => [
                'email' => $value,
                'status' => 'ready',
            ])
            ->getOrElse(['email' => 'noreply@example.com', 'status' => 'fallback']);

        self::assertSame('ada@example.com', $record['email']);
        self::assertSame('ready', $record['status']);
    }

    public function test_it_falls_back_when_optional_data_is_absent(): void
    {
        $record = Option::from(null)
            ->map(static fn (string $value): string => strtolower(trim($value)))
            ->orElseGet(static fn (): Option => Option::some('noreply@example.com'))
            ->toEither('missing email')
            ->fold(
                static fn (string $error): array => ['status' => 'error', 'message' => $error],
                static fn (string $email): array => ['status' => 'success', 'email' => $email],
            );

        self::assertSame([
            'status' => 'success',
            'email' => 'noreply@example.com',
        ], $record);
    }
}
