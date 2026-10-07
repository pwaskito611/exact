<?php

declare(strict_types=1);

namespace Exact\Tests\Integration\DomainModeling;

use Exact\Type\Either\Either;
use PHPUnit\Framework\TestCase;

final class EitherRegistrationFlowTest extends TestCase
{
    public function test_it_validates_an_email_and_completes_the_domain_record(): void
    {
        $result = Either::fromNullable(' ADA@EXAMPLE.COM ', 'email is required')
            ->map(static fn (string $value): string => strtolower(trim($value)))
            ->filter(
                static fn (string $value): bool => str_contains($value, '@'),
                'email is invalid',
            )
            ->flatMap(
                static fn (string $email): Either => Either::right([
                    'email' => $email,
                    'status' => 'ready',
                ]),
            );

        self::assertTrue($result->isRight());
        self::assertSame('ada@example.com', $result->getRight()['email']);
        self::assertSame('ready', $result->getRight()['status']);
    }

    public function test_it_returns_a_domain_error_when_input_is_missing(): void
    {
        $result = Either::fromNullable(null, 'email is required')
            ->filter(
                static fn (?string $value): bool => is_string($value) && str_contains($value, '@'),
                'email is invalid',
            )
            ->fold(
                static fn (string $error): array => ['status' => 'error', 'message' => $error],
                static fn (array $user): array => ['status' => 'success', 'user' => $user],
            );

        self::assertSame([
            'status' => 'error',
            'message' => 'email is required',
        ], $result);
    }
}
