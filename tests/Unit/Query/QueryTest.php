<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Query;

use Exact\Collection\Map;
use Exact\Collection\NonEmptyLazySeq;
use Exact\Collection\NonEmptyVec;
use Exact\Collection\Vec;
use Exact\Query\Query;
use Exact\Type\Option\Option;
use Exact\Type\Tuple\Pair;
use Exact\Type\Tuple\Tuple;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase
{
    public function testSelectProjectsDerivedValues(): void
    {
        $result = Query::from(Vec::of(
            ['profitLoss' => 10, 'openedAt' => '2024-01-01'],
            ['profitLoss' => -5, 'openedAt' => '2024-01-02'],
        ))
            ->select(static fn (array $trade): array => [
                'profitLoss' => $trade['profitLoss'],
                'openedAt' => $trade['openedAt'],
            ])
            ->execute();

        self::assertSame([
            ['profitLoss' => 10, 'openedAt' => '2024-01-01'],
            ['profitLoss' => -5, 'openedAt' => '2024-01-02'],
        ], $result->toArray());
    }

    public function testSelectProjectsIntoTupleAndReadonlyDomainValue(): void
    {
        $result = Query::from([['name' => 'Ari', 'score' => 7]])
            ->select(static fn (array $row): QueryTestProjection => new QueryTestProjection(
                $row['name'],
                Tuple::of2($row['score'], $row['score'] * 2),
            ))
            ->execute();

        self::assertInstanceOf(QueryTestProjection::class, $result->first());
        self::assertSame('Ari', $result->first()->name);
        self::assertSame([7, 14], $result->first()->scores->toArray());
    }

    public function testProjectionOfEmptyInputDoesNotInvokeCallback(): void
    {
        $calls = 0;
        $result = Query::from([])
            ->select(static function (mixed $value) use (&$calls): mixed {
                $calls++;
                return $value;
            })
            ->execute();

        self::assertSame([], $result->toArray());
        self::assertSame(0, $calls);
    }

    public function testQueryAcceptsNonEmptyVecWithoutCollectionSpecificQueryApi(): void
    {
        $result = Query::from(NonEmptyVec::of(1, 2, 3))
            ->where(static fn (int $value): bool => $value > 1)
            ->select(static fn (int $value): string => "value-{$value}")
            ->execute();

        self::assertSame(['value-2', 'value-3'], $result->toArray());
    }

    public function testQueryAcceptsNonEmptyLazySeqAndKeepsExecutionLazy(): void
    {
        $result = Query::from(NonEmptyLazySeq::iterate(
            1,
            static fn (int $value): int => $value + 1,
        ))
            ->select(static fn (int $value): int => $value * 2)
            ->executeLazy()
            ->take(3);

        self::assertSame([2, 4, 6], $result->toArray());
    }

    public function testQueryAcceptsMapValuesThroughIterableContract(): void
    {
        $result = Query::from(Map::of('first', 2, 'second', 4))
            ->select(static fn (int $value): int => $value * 10)
            ->execute();

        self::assertSame([20, 40], $result->toArray());
    }

    public function testJoinWhereAndGroupByCompose(): void
    {
        $orders = Vec::of(
            ['id' => 1, 'customer_id' => 10, 'total' => 100, 'region' => 'US'],
            ['id' => 2, 'customer_id' => 10, 'total' => 150, 'region' => 'US'],
            ['id' => 3, 'customer_id' => 20, 'total' => 200, 'region' => 'EU'],
        );
        $customers = Vec::of(
            ['id' => 10, 'name' => 'Alice'],
            ['id' => 20, 'name' => 'Bob'],
        );

        $result = Query::from($orders)
            ->join(
                $customers,
                static fn (array $order): int => $order['customer_id'],
                static fn (array $customer): int => $customer['id'],
                static fn (array $order, array $customer): array => [
                    'customer_name' => $customer['name'],
                    'region' => $order['region'],
                    'total' => $order['total'],
                ],
            )
            ->where(static fn (array $row): bool => $row['total'] >= 150)
            ->select(static fn (array $row): array => [
                'customer_name' => $row['customer_name'],
                'region' => $row['region'],
                'total' => $row['total'],
            ])
            ->groupBy(static fn (array $row): string => $row['region'])
            ->execute();

        self::assertInstanceOf(Map::class, $result);
        self::assertSame([
            ['customer_name' => 'Alice', 'region' => 'US', 'total' => 150],
        ], $result->get('US')->toArray());
        self::assertSame([
            ['customer_name' => 'Bob', 'region' => 'EU', 'total' => 200],
        ], $result->get('EU')->toArray());
    }

    public function testExecuteLazyPreservesDeferredProjection(): void
    {
        $result = Query::from([1, 2, 3])
            ->select(static fn (int $value): int => $value * 10)
            ->orderBy(static fn (int $value): int => $value, true)
            ->executeLazy();

        self::assertSame([30, 20, 10], $result->toArray());
    }

    public function testExecuteLazyDefersProjectionAndShortCircuitsTake(): void
    {
        $visited = 0;
        $query = Query::from([1, 2, 3, 4])
            ->select(static function (int $value) use (&$visited): int {
                $visited++;
                return $value * 10;
            })
            ->take(2);
        $lazy = $query->executeLazy();

        self::assertSame(0, $visited);
        self::assertSame([10, 20], $lazy->toArray());
        self::assertSame(2, $visited);
    }

    public function testOuterJoinsPreserveDuplicatesAndUseOptions(): void
    {
        $left = Vec::of(['key' => 1, 'value' => 'a'], ['key' => 1, 'value' => 'b'], ['key' => 3, 'value' => 'c']);
        $right = Vec::of(['key' => 1, 'value' => 'x'], ['key' => 1, 'value' => 'y'], ['key' => 2, 'value' => 'z']);

        $rows = Query::from($left)
            ->fullJoin(
                $right,
                static fn (array $row): int => $row['key'],
                static fn (array $row): int => $row['key'],
            )
            ->execute()
            ->toArray();

        self::assertCount(6, $rows);
        self::assertInstanceOf(Pair::class, $rows[0]);
        self::assertTrue($rows[0]->first()->isSome());
        self::assertTrue($rows[0]->second()->isSome());
        self::assertTrue($rows[4]->second()->isNone());
        self::assertTrue($rows[5]->first()->isNone());
        self::assertTrue($rows[5]->second()->isSome());
    }

    public function testLeftJoinCombinerReceivesNoneForUnmatchedRows(): void
    {
        $result = Query::from([1, 2])
            ->leftJoin(
                [2],
                static fn (int $value): int => $value,
                static fn (int $value): int => $value,
                static fn (int $left, Option $right): string => $right->fold(
                    static fn (): string => "{$left}:missing",
                    static fn (int $value): string => "{$left}:{$value}",
                ),
            )
            ->execute();

        self::assertSame(['1:missing', '2:2'], $result->toArray());
    }

    public function testSemiAndAntiJoinKeepLeftOrderAndDuplicates(): void
    {
        $left = Vec::of(1, 2, 1, 3);
        $right = Vec::of(1, 1, 4);
        $leftKey = static fn (int $value): int => $value;
        $rightKey = static fn (int $value): int => $value;

        self::assertSame([1, 1], Query::from($left)->semiJoin($right, $leftKey, $rightKey)->execute()->toArray());
        self::assertSame([2, 3], Query::from($left)->antiJoin($right, $leftKey, $rightKey)->execute()->toArray());
    }

    public function testRightAndCrossJoinsPreserveExpectedOutputOrder(): void
    {
        $right = Query::from(['a', 'b'])
            ->rightJoin(
                ['b', 'c'],
                static fn (string $value): string => $value,
                static fn (string $value): string => $value,
            )
            ->execute()
            ->toArray();
        self::assertTrue($right[0]->first()->isSome());
        self::assertSame('b', $right[0]->second());
        self::assertTrue($right[1]->first()->isNone());
        self::assertSame('c', $right[1]->second());

        $cross = Query::from([1, 2])
            ->crossJoin(['x', 'y'])
            ->execute();
        self::assertSame([
            [1, 'x'],
            [1, 'y'],
            [2, 'x'],
            [2, 'y'],
        ], array_map(static fn (Pair $pair): array => $pair->toArray(), $cross->toArray()));
    }

    public function testWhereInTakeAndFirstShortCircuit(): void
    {
        $visited = 0;
        $source = (static function () use (&$visited): \Generator {
            for ($value = 1; $value < 100; $value++) {
                $visited++;
                yield $value;
            }
        })();

        $first = Query::from($source)
            ->whereIn([3, 5, 7])
            ->firstOrNone();

        self::assertSame(3, $first->getOrNull());
        self::assertSame(3, $visited);
    }

    public function testWhereNotInAndDistinctByUseStableSetSemantics(): void
    {
        $result = Query::from([
            ['id' => 1, 'team' => 'a'],
            ['id' => 2, 'team' => 'a'],
            ['id' => 3, 'team' => 'b'],
        ])
            ->whereNotIn(['b'], static fn (array $row): string => $row['team'])
            ->distinctBy(static fn (array $row): string => $row['team'])
            ->execute();

        self::assertSame([['id' => 1, 'team' => 'a']], $result->toArray());
    }

    public function testGroupingHavingAndSinglePassGroupFold(): void
    {
        $items = Vec::of(
            ['team' => 'a', 'score' => 2],
            ['team' => 'a', 'score' => 3],
            ['team' => 'b', 'score' => 8],
        );

        $grouped = Query::from($items)
            ->groupBy(static fn (array $item): string => $item['team'])
            ->having(static fn (Vec $group): bool => $group->count() > 1)
            ->select(static fn (Vec $group): int => $group->count())
            ->execute();

        self::assertSame(['a' => 2], $grouped->toArray());
        self::assertSame(['a' => 5, 'b' => 8], Query::from($items)->groupFold(
            static fn (array $item): string => $item['team'],
            0,
            static fn (int $sum, array $item): int => $sum + $item['score'],
        )->toArray());
        self::assertSame(['a' => 5, 'b' => 8], Query::from($items)->aggregateBy(
            static fn (array $item): string => $item['team'],
            static fn (Vec $group): int => $group->foldLeft(0, static fn (int $sum, array $item): int => $sum + $item['score']),
        )->toArray());
    }

    public function testSetOperationsDefineStableDuplicateSemantics(): void
    {
        self::assertSame([1, 2, 3], Query::from([1, 1, 2])->union([2, 3, 3])->execute()->toArray());
        self::assertSame([1, 1, 2, 2, 3, 3], Query::from([1, 1, 2])->unionAll([2, 3, 3])->execute()->toArray());
        self::assertSame([2], Query::from([1, 2, 2, 3])->intersect([2, 4])->execute()->toArray());
        self::assertSame([1, 3], Query::from([1, 2, 2, 3])->except([2])->execute()->toArray());
    }

    public function testSetOperationsSupportCustomEquality(): void
    {
        $equalsId = static fn (array $left, array $right): bool => $left['id'] === $right['id'];
        $result = Query::from([['id' => 1, 'source' => 'left']])
            ->union([['id' => 1, 'source' => 'right'], ['id' => 2, 'source' => 'right']], equals: $equalsId)
            ->execute();

        self::assertSame([
            ['id' => 1, 'source' => 'left'],
            ['id' => 2, 'source' => 'right'],
        ], $result->toArray());
    }

    public function testOrderBySupportsStableMultipleCriteriaAndPrecomputesSelectors(): void
    {
        $calls = 0;
        $result = Query::from([
            ['group' => 'b', 'rank' => 1, 'id' => 1],
            ['group' => 'a', 'rank' => 2, 'id' => 2],
            ['group' => 'a', 'rank' => 1, 'id' => 3],
        ])
            ->orderBy(static fn (array $row): string => $row['group'])
            ->thenBy(static function (array $row) use (&$calls): int {
                $calls++;
                return $row['rank'];
            })
            ->execute();

        self::assertSame([3, 2, 1], array_column($result->toArray(), 'id'));
        self::assertSame(3, $calls);
    }

    public function testAggregateOperationsHandleEmptyAndNumericInputs(): void
    {
        self::assertSame(0, Query::from([])->sum());
        self::assertTrue(Query::from([])->mean()->isNone());
        self::assertSame(2.5, Query::from([1, 2, 3, 4])->mean()->getOrNull());
        self::assertSame(4, Query::from([1, 4, 2])->max()->getOrNull());
        self::assertSame(2, Query::from([1, 4, 2])->minBy(static fn (int $n): int => abs($n - 2))->getOrNull());
        self::assertSame(['a' => 2, 'b' => 1], Query::from(['a', 'a', 'b'])->countBy(static fn (string $x): string => $x)->toArray());
        self::assertSame(['a' => 5], Query::from([['k' => 'a', 'n' => 2], ['k' => 'a', 'n' => 3]])
            ->sumBy(static fn (array $x): string => $x['k'], static fn (array $x): int => $x['n'])
            ->toArray());
    }

    public function testQueryDataOperationsComposeAndWindowHonorsStep(): void
    {
        $result = Query::from([1, 2, 3, 4, 5, 6, 7])
            ->flatMap(static fn (int $value): array => [$value, $value * 10])
            ->filterMap(static fn (int $value): ?int => $value < 5 ? $value : null)
            ->scan(0, static fn (int $sum, int $value): int => $sum + $value)
            ->window(2, 3)
            ->execute();

        self::assertSame([[0, 1], [6, 10]], array_map(static fn (Vec $window): array => $window->toArray(), $result->toArray()));
    }

    public function testBoundsFoldWhileAndZipOperations(): void
    {
        $bounded = Query::from([1, 2, 3, 4, 5, 6])
            ->skipWhile(static fn (int $value): bool => $value < 3)
            ->takeWhile(static fn (int $value): bool => $value < 5);
        self::assertSame([3, 4], $bounded->execute()->toArray());
        self::assertSame(3, Query::from([1, 2, 4, 3])->foldWhile(
            0,
            static fn (int $sum, int $value): bool => $value < 4,
            static fn (int $sum, int $value): int => $sum + $value,
        ));

        $pairs = Query::from([1, 2, 3])
            ->zip(['a', 'b'])
            ->execute();
        self::assertSame([[1, 'a'], [2, 'b']], array_map(
            static fn (Pair $pair): array => $pair->toArray(),
            $pairs->toArray(),
        ));
        self::assertSame([11, 22], Query::from([1, 2])
            ->zipWith([10, 20], static fn (int $left, int $right): int => $left + $right)
            ->execute()
            ->toArray());
    }

    public function testEmptyQueryTerminalsAreExplicit(): void
    {
        self::assertFalse(Query::from([])->exists(static fn (mixed $_): bool => true));
        self::assertTrue(Query::from([])->firstOrNone()->isNone());
        self::assertTrue(Query::from([])->lastOrNone()->isNone());
        self::assertSame([], Query::from([])->groupBy(static fn (mixed $_): string => 'all')->execute()->toArray());
    }

    public function testExplainAndProfileReportExecutionWithoutRerunningSource(): void
    {
        $query = Query::from([1, 2, 3])
            ->where(static fn (int $value): bool => $value > 1)
            ->select(static fn (int $value): int => $value * 2);

        self::assertSame(['where', 'select'], array_column($query->explain()['stages'], 'operation'));
        $profile = $query->profile();
        self::assertSame([4, 6], $profile['result']->toArray());
        self::assertSame(3, $profile['statistics']['source_elements']);
        self::assertSame(2, $profile['statistics']['output_elements']);
        self::assertGreaterThan(0, $profile['statistics']['duration_ns']);
    }

    public function testProfileConsumesOneShotSourceOnlyOnce(): void
    {
        $consumed = 0;
        $source = (static function () use (&$consumed): \Generator {
            foreach ([4, 5, 6] as $value) {
                $consumed++;
                yield $value;
            }
        })();

        $profile = Query::from($source)->select(static fn (int $value): int => $value + 1)->profile();

        self::assertSame([5, 6, 7], $profile['result']->toArray());
        self::assertSame(3, $consumed);
        self::assertSame(3, $profile['statistics']['source_elements']);
    }

    public function testInvalidWindowAndJoinKeysFailExplicitly(): void
    {
        try {
            Query::from([1])->window(0);
            self::fail('Expected window validation to fail.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        Query::from([1])->innerJoin(
            [1],
            static fn (int $value): null => null,
            static fn (int $value): int => $value,
        )->execute();
    }
}

final readonly class QueryTestProjection
{
    public function __construct(
        public string $name,
        public Tuple $scores,
    ) {
    }
}
