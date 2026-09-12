<?php

declare(strict_types=1);

namespace Exact\Tests\Collections;

use Exact\Collections\ImmutableList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImmutableList::class)]
final class ImmutableListTest extends TestCase
{
    public function testMapCreatesNewListWithoutMutatingOriginal(): void
    {
        $list = ImmutableList::fromArray([1, 2, 3]);

        $mapped = $list->map(static fn (int $value) => $value * 2);

        $this->assertSame([1, 2, 3], $list->toArray());
        $this->assertSame([2, 4, 6], $mapped->toArray());
    }

    public function testAppendAddsValueToEnd(): void
    {
        $list = ImmutableList::fromArray([1, 2]);

        $appended = $list->append(3);

        $this->assertSame([1, 2, 3], $appended->toArray());
    }

    public function testFilterRemovesValues(): void
    {
        $list = ImmutableList::fromArray([1, 2, 3, 4]);

        $filtered = $list->filter(static fn (int $value) => $value % 2 === 0);

        $this->assertSame([2, 4], $filtered->toArray());
    }
}
