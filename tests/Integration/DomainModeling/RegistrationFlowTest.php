<?php

declare(strict_types=1);

namespace Exact\Tests\Integration\DomainModeling;

use Exact\Data\Option\Option;
use Exact\Data\Result\Result;
use Exact\Data\Validated\Validated;
use Exact\Value\Newtype;
use PHPUnit\Framework\TestCase;

final class RegistrationFlowTest extends TestCase
{
    public function test_it_validates_optional_input_before_creating_a_domain_result(): void
    {
        $userId = new RegistrationUserId('user-42');
        $email = Option::from('ADA@EXAMPLE.COM')
            ->map(static fn (string $value): string => strtolower($value));
        $validatedEmail = $email->fold(
            static fn (): Validated => Validated::invalid('email is required'),
            static fn (string $value): Validated => str_contains($value, '@')
                ? Validated::valid($value)
                : Validated::invalid('email is invalid'),
        );

        $registration = $validatedEmail->map(
            static fn (string $value): array => [
                'id' => $userId,
                'email' => $value,
            ],
        );
        $result = $registration->fold(
            static fn (string $error): Result => Result::err($error),
            static fn (array $user): Result => Result::ok($user),
        );

        self::assertTrue($result->isOk());
        self::assertSame('user-42', $result->get()['id']->value());
        self::assertSame('ada@example.com', $result->get()['email']);
    }
}

final readonly class RegistrationUserId extends Newtype
{
}