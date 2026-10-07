<?php

declare(strict_types=1);

namespace Exact\Tests\Integration\DomainModeling;

use Exact\Type\Either\Either;
use Exact\Type\Option\Option;
use Exact\Type\Validated\Validated;
use PHPUnit\Framework\TestCase;

final class RegistrationFlowTest extends TestCase
{
    public function test_it_validates_optional_input_before_creating_a_domain_result(): void
    {
        $email = Option::from('ADA@EXAMPLE.COM')
            ->map(static fn (string $value): string => strtolower($value));

        $validatedEmail = $email->fold(
            static fn (): Validated => Validated::invalid('email is required'),
            static fn (string $value): Validated => str_contains($value, '@')
                ? Validated::valid($value)
                : Validated::invalid('email is invalid'),
        );

        $result = $validatedEmail->fold(
            static fn (string $error): Either => Either::left($error),
            static fn (string $value): Either => Either::right([
                'email' => $value,
                'status' => 'ready',
            ]),
        );

        self::assertTrue($result->isRight());
        self::assertSame('ada@example.com', $result->getRight()['email']);
        self::assertSame('ready', $result->getRight()['status']);
    }
}