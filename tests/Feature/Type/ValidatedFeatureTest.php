<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Type;

use Exact\Type\Validated\Validated;
use PHPUnit\Framework\TestCase;

final class ValidatedFeatureTest extends TestCase
{
    public function test_it_combines_independent_validations(): void
    {
        $validatedName = Validated::valid('Ada');
        $validatedEmail = Validated::valid('ada@example.com');

        $profile = $validatedName->combine(
            $validatedEmail,
            static fn (string $name, string $email): array => [$name, $email],
        );

        self::assertTrue($profile->isValid());
        self::assertSame(
            ['Ada', 'ada@example.com'],
            $profile->getValid(),
        );
    }

    public function test_it_accumulates_two_invalid_inputs(): void
    {
        $errors = Validated::invalid(['name' => 'required'])->combine(
            Validated::invalid(['email' => 'invalid']),
            static fn (array $first, array $second): array => [
                ...$first,
                ...$second,
            ],
        );

        self::assertTrue($errors->isInvalid());
        self::assertSame(
            ['name' => 'required', 'email' => 'invalid'],
            $errors->getInvalid(),
        );
    }

    public function test_it_supports_eager_branch_transformation_and_laziness(): void
    {
        $value = Validated::valid('ada@example.com')
            ->bimap(
                static fn (string $message): string => strtoupper($message),
                static fn (string $email): string => strtoupper($email),
            )
            ->getOrElseGet(static fn (): string => 'fallback');

        self::assertSame('ADA@EXAMPLE.COM', $value);
    }
}