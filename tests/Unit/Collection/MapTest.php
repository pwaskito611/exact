<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Collection;

use Exact\Collection\Map;
use PHPUnit\Framework\TestCase;

final class MapTest extends TestCase
{
    public function testEmptyCreatesEmptyMap(): void
    {
        $map = Map::empty();

        self::assertTrue($map->isEmpty());
        self::assertSame(0, $map->size());
    }

    public function testOfCreatesMap(): void
    {
        $map = Map::of(
            'name', 'Pandu',
            'age', 25,
        );

        self::assertSame(
            [
                'name' => 'Pandu',
                'age' => 25,
            ],
            $map->toArray(),
        );
    }

    public function testOfRejectsOddNumberOfArguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Map::of('name', 'Pandu', 'age');
    }

    public function testFromArrayCreatesMap(): void
    {
        $map = Map::fromArray([
            'a' => 1,
            'b' => 2,
        ]);

        self::assertSame(
            ['a' => 1, 'b' => 2],
            $map->toArray(),
        );
    }

    public function testHasReturnsWhetherKeyExists(): void
    {
        $map = Map::of('name', 'Pandu');

        self::assertTrue($map->has('name'));
        self::assertFalse($map->has('age'));
    }

    public function testGetReturnsValue(): void
    {
        $map = Map::of('name', 'Pandu');

        self::assertSame('Pandu', $map->get('name'));
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $map = Map::empty();

        self::assertNull($map->get('missing'));
    }

    public function testGetOrElseReturnsFallback(): void
    {
        $map = Map::empty();

        self::assertSame(
            'Unknown',
            $map->getOrElse('name', 'Unknown'),
        );
    }

    public function testPutReturnsNewMap(): void
    {
        $map = Map::of('a', 1);

        $result = $map->put('b', 2);

        self::assertSame(['a' => 1], $map->toArray());
        self::assertSame(
            ['a' => 1, 'b' => 2],
            $result->toArray(),
        );
    }

    public function testPutReplacesExistingValue(): void
    {
        $map = Map::of('a', 1);

        $result = $map->put('a', 2);

        self::assertSame(['a' => 2], $result->toArray());
    }

    public function testRemoveRemovesKey(): void
    {
        $map = Map::of(
            'a', 1,
            'b', 2,
        );

        $result = $map->remove('a');

        self::assertSame(['b' => 2], $result->toArray());
    }

    public function testMapTransformsValues(): void
    {
        $map = Map::of(
            'a', 1,
            'b', 2,
        );

        $result = $map->map(
            fn (int $value) => $value * 10,
        );

        self::assertSame(
            [
                'a' => 10,
                'b' => 20,
            ],
            $result->toArray(),
        );
    }

    public function testMapReceivesKey(): void
    {
        $map = Map::of(
            'a', 1,
            'b', 2,
        );

        $result = $map->map(
            fn (int $value, string $key) => $key . $value,
        );

        self::assertSame(
            [
                'a' => 'a1',
                'b' => 'b2',
            ],
            $result->toArray(),
        );
    }
}