<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Exact\Query\Query;

$orders = [];
$customers = [];
$orderCount = 5000;
$customerCount = 500;

for ($i = 0; $i < $orderCount; $i++) {
    $orders[] = ['id' => $i, 'customer_id' => $i % $customerCount];
}

for ($i = 0; $i < $customerCount; $i++) {
    $customers[] = ['id' => $i, 'name' => 'Customer-' . $i];
}

$startedAt = hrtime(true);
$nestedCount = 0;
foreach ($orders as $order) {
    foreach ($customers as $customer) {
        if ($order['customer_id'] === $customer['id']) {
            $nestedCount++;
        }
    }
}
$nestedMs = (hrtime(true) - $startedAt) / 1_000_000;

$startedAt = hrtime(true);
$queryCount = Query::from($orders)
    ->innerJoin(
        $customers,
        static fn (array $order): int => $order['customer_id'],
        static fn (array $customer): int => $customer['id'],
    )
    ->count();
$queryMs = (hrtime(true) - $startedAt) / 1_000_000;

printf(
    "orders=%d customers=%d nested_ms=%.2f query_ms=%.2f nested_rows=%d query_rows=%d\n",
    $orderCount,
    $customerCount,
    $nestedMs,
    $queryMs,
    $nestedCount,
    $queryCount,
);
