<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Collection;

use Exact\Collection\Map;
use Exact\Collection\Vec;
use Exact\Exception\EmptyCollectionException;
use Exact\Type\Option\Option;
use PHPUnit\Framework\TestCase;

final class VecTest extends TestCase
{
    public function testEmptyCreatesEmptySequence(): void
    {
        $seq = Vec::empty();

        self::assertTrue($seq->isEmpty());
        self::assertSame(0, $seq->size());
    }

    public function testOfCreatesSequence(): void
    {
        $seq = Vec::of(1, 2, 3);

        self::assertSame([1, 2, 3], $seq->toArray());
    }

    public function testFromArrayCreatesSequence(): void
    {
        $seq = Vec::fromArray([1, 2, 3]);

        self::assertSame([1, 2, 3], $seq->toArray());
    }

    public function testSizeReturnsNumberOfElements(): void
    {
        $seq = Vec::of(1, 2, 3);

        self::assertSame(3, $seq->size());
    }

    public function testHeadReturnsFirstElement(): void
    {
        $seq = Vec::of(10, 20, 30);

        self::assertSame(10, $seq->head());
    }

    public function testHeadThrowsOnEmptySequence(): void
    {
        $this->expectException(EmptyCollectionException::class);

        Vec::empty()->head();
    }

    public function testTailReturnsRemainingElements(): void
    {
        $seq = Vec::of(1, 2, 3);

        self::assertSame(
            [2, 3],
            $seq->tail()->toArray(),
        );
    }

    public function testMapTransformsElements(): void
    {
        $result = Vec::of(1, 2, 3)
            ->map(fn (int $value) => $value * 2);

        self::assertSame([2, 4, 6], $result->toArray());
    }

    public function testFilterKeepsMatchingElements(): void
    {
        $result = Vec::of(1, 2, 3, 4)
            ->filter(fn (int $value) => $value % 2 === 0);

        self::assertSame([2, 4], $result->toArray());
    }

    public function testAppendAddsElement(): void
    {
        $seq = Vec::of(1, 2);

        $result = $seq->append(3);

        self::assertSame([1, 2], $seq->toArray());
        self::assertSame([1, 2, 3], $result->toArray());
    }

    public function testPrependAddsElementAtBeginning(): void
    {
        $result = Vec::of(2, 3)->prepend(1);

        self::assertSame([1, 2, 3], $result->toArray());
    }

    public function testContainsUsesStrictComparison(): void
    {
        $seq = Vec::of(1, '1');

        self::assertTrue($seq->contains(1));
        self::assertTrue($seq->contains('1'));
        self::assertFalse($seq->contains(true));
    }

    public function testFoldLeftAccumulatesValues(): void
    {
        $result = Vec::of(1, 2, 3, 4)
            ->foldLeft(
                0,
                fn (int $sum, int $value) => $sum + $value,
            );

        self::assertSame(10, $result);
    }

    public function testFilterMapTransformsAndDropsEmptyOptions(): void
    {
        $result = Vec::of(1, 2, 3, 4)
            ->filterMap(
                fn (int $value): Option => $value % 2 === 0
                    ? Option::some($value * 10)
                    : Option::none(),
            );

        self::assertSame([20, 40], $result->toArray());
    }

    public function testPartitionSeparatesMatchingValues(): void
    {
        [$evens, $odds] = Vec::of(1, 2, 3, 4)
            ->partition(fn (int $value): bool => $value % 2 === 0);

        self::assertSame([2, 4], $evens->toArray());
        self::assertSame([1, 3], $odds->toArray());
    }

    public function testSelectProjectsRecordsIntoDerivedValues(): void
    {
        $result = Vec::of(
            ['id' => 1, 'profitLoss' => 10, 'openedAt' => '2024-01-01'],
            ['id' => 2, 'profitLoss' => -5, 'openedAt' => '2024-01-02'],
        )
            ->select(
                static fn (array $trade): array => [
                    'id' => $trade['id'],
                    'profitLoss' => $trade['profitLoss'],
                ],
            );

        self::assertSame([
            ['id' => 1, 'profitLoss' => 10],
            ['id' => 2, 'profitLoss' => -5],
        ], $result->toArray());
    }

    public function testProjectAliasMatchesSelectSemantics(): void
    {
        $result = Vec::of(1, 2, 3)
            ->project(static fn (int $value): int => $value * 10);

        self::assertSame([10, 20, 30], $result->toArray());
    }

    public function testSelectAfterJoinProducesProjectedRows(): void
    {
        $orders = Vec::of(
            ['id' => 1, 'customer_id' => 10, 'total' => 100],
            ['id' => 2, 'customer_id' => 20, 'total' => 200],
        );
        $customers = Vec::of(
            ['id' => 10, 'name' => 'Alice'],
            ['id' => 20, 'name' => 'Bob'],
        );

        $result = $orders->innerJoin(
            $customers,
            static fn (array $order): int => $order['customer_id'],
            static fn (array $customer): int => $customer['id'],
            static fn (array $order, array $customer): array => [
                'order_id' => $order['id'],
                'customer_name' => $customer['name'],
                'total' => $order['total'],
            ],
        )->select(
            static fn (array $row): array => [
                'report' => $row['customer_name'] . ':' . $row['total'],
            ],
        );

        self::assertSame([
            ['report' => 'Alice:100'],
            ['report' => 'Bob:200'],
        ], $result->toArray());
    }

    public function testScanProducesRunningTotals(): void
    {
        $result = Vec::of(1, 2, 3)
            ->scan(0, fn (int $total, int $value): int => $total + $value);

        self::assertSame([0, 1, 3, 6], $result->toArray());
    }

    public function testDistinctByKeepsFirstUniqueKey(): void
    {
        $result = Vec::of('apple', 'banana', 'apricot', 'pear')
            ->distinctBy(fn (string $value): string => substr($value, 0, 1));

        self::assertSame(['apple', 'banana', 'pear'], $result->toArray());
    }

    public function testGroupByBuildsMapOfVecBuckets(): void
    {
        $result = Vec::of(['team' => 'a', 'score' => 1], ['team' => 'b', 'score' => 2], ['team' => 'a', 'score' => 3])
            ->groupBy(fn (array $row): string => $row['team']);

        self::assertInstanceOf(Map::class, $result);
        self::assertSame([['team' => 'a', 'score' => 1], ['team' => 'a', 'score' => 3]], $result->get('a')->toArray());
        self::assertSame([['team' => 'b', 'score' => 2]], $result->get('b')->toArray());
    }

    public function testInnerJoinPairsMatchingRecords(): void
    {
        $orders = Vec::of(
            ['id' => 1, 'customer_id' => 10],
            ['id' => 2, 'customer_id' => 20],
            ['id' => 3, 'customer_id' => 10],
        );
        $customers = Vec::of(
            ['id' => 10, 'name' => 'Alice'],
            ['id' => 20, 'name' => 'Bob'],
            ['id' => 20, 'name' => 'Bob-2'],
        );

        $result = $orders->innerJoin(
            $customers,
            static fn (array $order): int => $order['customer_id'],
            static fn (array $customer): int => $customer['id'],
            static fn (array $order, array $customer): array => [
                'order_id' => $order['id'],
                'customer_name' => $customer['name'],
            ],
        );

        self::assertSame([
            ['order_id' => 1, 'customer_name' => 'Alice'],
            ['order_id' => 2, 'customer_name' => 'Bob'],
            ['order_id' => 2, 'customer_name' => 'Bob-2'],
            ['order_id' => 3, 'customer_name' => 'Alice'],
        ], $result->toArray());
    }

    public function testLeftJoinPreservesUnmatchedLeftRecords(): void
    {
        $orders = Vec::of(
            ['id' => 1, 'customer_id' => 10],
            ['id' => 2, 'customer_id' => 99],
        );
        $customers = Vec::of(
            ['id' => 10, 'name' => 'Alice'],
        );

        $result = $orders->leftJoin(
            $customers,
            static fn (array $order): int => $order['customer_id'],
            static fn (array $customer): int => $customer['id'],
            static fn (array $order, Option $customer): array => [
                'order_id' => $order['id'],
                'customer' => $customer->getOrNull(),
            ],
        );

        self::assertSame([
            ['order_id' => 1, 'customer' => ['id' => 10, 'name' => 'Alice']],
            ['order_id' => 2, 'customer' => null],
        ], $result->toArray());
    }

    public function testRightJoinPreservesUnmatchedRightRecords(): void
    {
        $orders = Vec::of(
            ['id' => 1, 'customer_id' => 10],
        );
        $customers = Vec::of(
            ['id' => 10, 'name' => 'Alice'],
            ['id' => 20, 'name' => 'Bob'],
        );

        $result = $orders->rightJoin(
            $customers,
            static fn (array $order): int => $order['customer_id'],
            static fn (array $customer): int => $customer['id'],
            static fn (Option $order, array $customer): array => [
                'order' => $order->getOrNull(),
                'customer_name' => $customer['name'],
            ],
        );

        self::assertSame([
            ['order' => ['id' => 1, 'customer_id' => 10], 'customer_name' => 'Alice'],
            ['order' => null, 'customer_name' => 'Bob'],
        ], $result->toArray());
    }

    public function testFullJoinKeepsMatchedAndUnmatchedRecords(): void
    {
        $orders = Vec::of(
            ['id' => 1, 'customer_id' => 10],
            ['id' => 2, 'customer_id' => 99],
        );
        $customers = Vec::of(
            ['id' => 10, 'name' => 'Alice'],
            ['id' => 30, 'name' => 'Carol'],
        );

        $result = $orders->fullJoin(
            $customers,
            static fn (array $order): int => $order['customer_id'],
            static fn (array $customer): int => $customer['id'],
            static fn (Option $order, Option $customer): array => [
                'order' => $order->getOrNull(),
                'customer' => $customer->getOrNull(),
            ],
        );

        self::assertSame([
            ['order' => ['id' => 1, 'customer_id' => 10], 'customer' => ['id' => 10, 'name' => 'Alice']],
            ['order' => ['id' => 2, 'customer_id' => 99], 'customer' => null],
            ['order' => null, 'customer' => ['id' => 30, 'name' => 'Carol']],
        ], $result->toArray());
    }

    public function testCrossJoinProducesCartesianProduct(): void
    {
        $left = Vec::of('A', 'B');
        $right = Vec::of(1, 2);

        $result = $left->crossJoin(
            $right,
            static fn (string $left, int $right): array => [$left, $right],
        );

        self::assertSame([
            ['A', 1],
            ['A', 2],
            ['B', 1],
            ['B', 2],
        ], $result->toArray());
    }

    public function testOperationsDoNotMutateOriginalSequence(): void
    {
        $seq = Vec::of(1, 2, 3);

        $mapped = $seq->map(
            fn (int $value) => $value * 2,
        );

        self::assertSame([1, 2, 3], $seq->toArray());
        self::assertSame([2, 4, 6], $mapped->toArray());
    }
}