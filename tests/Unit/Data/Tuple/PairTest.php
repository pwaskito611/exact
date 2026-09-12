<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Tuple;

use Exact\Data\Tuple\Pair;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Pair::class)]
final class PairTest extends TestCase
{
    public function testPairStoresTwoValues(): void
    {
        $pair = Pair::of('first', 'second');

        $this->assertSame('first', $pair->first());
        $this->assertSame('second', $pair->second());
        $this->assertSame(['first', 'second'], $pair->toArray());
    }

    public function testPairCanBeSwapped(): void
    {
        $pair = Pair::of('left', 'right');

        $swapped = $pair->swap();

        $this->assertSame('right', $swapped->first());
        $this->assertSame('left', $swapped->second());
    }
}
