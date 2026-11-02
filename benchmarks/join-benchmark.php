<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Exact\Collection\Vec;

$orderCount = 5000;
$customerCount = 500;

$orders = [];
$customers = [];

for ($i = 0; $i < $orderCount; $i++) {
    $orders[] = ['id' => $i, 'customer_id' => $i % $customerCount];
}

for ($i = 0; $i < $customerCount; $i++) {
    $customers[] = ['id' => $i, 'name' => 'Customer-' . $i];
}

$start = hrtime(true);
$native = [];
foreach ($orders as $order) {
    foreach ($customers as $customer) {
        if ($order['customer_id'] === $customer['id']) {
            $native[] = ['order_id' => $order['id'], 'customer_name' => $customer['name']];
        }
    }
}
$nativeTime = (hrtime(true) - $start) / 1_000_000;
$nativePeak = memory_get_peak_usage(true);

$start = hrtime(true);
$exact = Vec::fromArray($orders)
    ->innerJoin(
        Vec::fromArray($customers),
        static fn (array $order): int => $order['customer_id'],
        static fn (array $customer): int => $customer['id'],
        static fn (array $order, array $customer): array => [
            'order_id' => $order['id'],
            'customer_name' => $customer['name'],
        ],
    )
    ->toArray();
$exactTime = (hrtime(true) - $start) / 1_000_000;
$exactPeak = memory_get_peak_usage(true);

printf("orders=%d customers=%d native_ms=%.2f exact_ms=%.2f native_rows=%d exact_rows=%d native_peak_bytes=%d exact_peak_bytes=%d\n",
    $orderCount,
    $customerCount,
    $nativeTime,
    $exactTime,
    count($native),
    count($exact),
    $nativePeak,
    $exactPeak,
);
