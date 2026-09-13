<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Data;

use Exact\Data\Validated\Validated;
use PHPUnit\Framework\TestCase;

final class ValidatedFeatureTest extends TestCase
{
    public function test_it_combines_independent_validations(): void
    {
        $validatedName = Validated::valid('Ada');
        $validatedEmail = Validated::valid('ada@example.com');

        $profile = $validatedName->combine(
            $validatedEmail,
            static fn (string $name, string $email): array => [
                'name' => $name,
                'email' => $email,
            ],
            static fn (mixed $first, mixed $second): array => [$first, $second],
        );

        self::assertTrue($profile->isValid());
        self::assertSame(
            ['name' => 'Ada', 'email' => 'ada@example.com'],
            $profile->getValid(),
        );
    }

    public function test_it_accumulates_two_invalid_inputs(): void
    {
        $errors = Validated::invalid(['name' => 'required'])->combine(
            Validated::invalid(['email' => 'invalid']),
            static fn (mixed $first, mixed $second): array => [$first, $second],
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
}