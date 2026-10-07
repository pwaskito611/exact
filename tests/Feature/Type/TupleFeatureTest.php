<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Type;

use Exact\Type\Tuple\Pair;
use Exact\Type\Tuple\Tuple;
use PHPUnit\Framework\TestCase;

final class TupleFeatureTest extends TestCase
{
    public function test_it_transforms_a_tuple_and_swaps_a_pair(): void
    {
        $coordinates = Tuple::of(10, 20)
            ->mapAt(0, static fn (int $coordinate): int => $coordinate * 2)
            ->mapAt(1, static fn (int $coordinate): int => $coordinate * 2);
        $pair = Pair::of('left', 'right')->swap();

        self::assertSame([20, 40], $coordinates->toArray());
        self::assertSame(['right', 'left'], $pair->toArray());
    }
}