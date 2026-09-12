# Dokumen Penggunaan (Usage)

## 1. Pendahuluan

Exact adalah library PHP untuk domain modeling yang lebih ekspresif, aman, dan immutable. Tujuannya bukan untuk mengubah PHP menjadi Scala, tetapi untuk menambahkan abstraction yang benar-benar membantu dalam model bisnis.

## 2. Instalasi

```bash
composer require exact/exact
```

## 3. Import dasar

```php
<?php

use Exact\Option;
use Exact\Result;
use Exact\Either;
use Exact\Validated;
use Exact\Collections\ImmutableList;
```

## 4. Option

`Option` mewakili nilai yang mungkin ada atau tidak ada.

```php
<?php

use Exact\Option;

$email = Option::from('user@example.com');

$result = $email
    ->map(static fn (string $value) => strtolower($value))
    ->filter(static fn (string $value) => str_contains($value, '@'));

var_dump($result->getOrElse('unknown@example.com'));
```

### Pattern umum

```php
$maybeUser = Option::from($user);

$displayName = $maybeUser
    ->map(static fn ($user) => $user->name)
    ->getOrElse('Guest');
```

### Keuntungan

- menghindari `null` berantakan
- clearer semantic
- pipeline transformasi yang aman

## 5. Result

`Result` memodelkan operasi yang berhasil atau gagal.

```php
<?php

use Exact\Result;

$result = Result::ok(10)
    ->map(static fn (int $n) => $n * 2)
    ->flatMap(static fn (int $n) => Result::ok($n + 5));

var_dump($result->getOrElse(0));
```

Contoh error:

```php
<?php

use Exact\Result;

$payment = Result::err('insufficient_funds');

$final = $payment->onFailure(static function (string $message) {
    echo "Payment failed: {$message}";
});
```

## 6. Either

`Either` digunakan untuk left/right semantics, umum dipakai untuk error/success yang dibedakan dengan tipe yang jelas.

```php
<?php

use Exact\Either;

$validated = Either::right(['id' => 42])
    ->map(static fn (array $payload) => $payload['id'])
    ->flatMap(static fn (int $id) => Either::right($id + 1));

var_dump($validated->getOrElse(0));
```

## 7. Validated

`Validated` berguna untuk validasi multi-field sekaligus.

```php
<?php

use Exact\Validated;

$rules = Validated::success(['name' => 'Alice'])
    ->combine(Validated::fail(['email' => 'invalid']));

if ($rules->isInvalid()) {
    var_dump($rules->getErrors());
}
```

## 8. ImmutableList

```php
<?php

use Exact\Collections\ImmutableList;

$list = ImmutableList::fromArray([1, 2, 3]);

$mapped = $list
    ->map(static fn (int $value) => $value * 10)
    ->filter(static fn (int $value) => $value > 20);

var_dump($mapped->toArray());
```

## 9. Kapan memakai tipe ini

### Gunakan `Option` jika:
- ada data yang mungkin kosong
- ingin menghindari `null` eksplisit di domain logic

### Gunakan `Result` jika:
- operasi bisa berhasil atau gagal
- ingin error handling yang jelas

### Gunakan `Either` jika:
- perlu dua branch dengan makna domain yang berbeda
- membedakan left dan right secara eksplisit

### Gunakan `Validated` jika:
- validasi melibatkan beberapa error sekaligus
- ingin mengumpulkan kesalahan daripada gagal pada satu error pertama

## 10. Prinsip penggunaan

- tetap gunakan PHP native seperti enum, readonly, class, match
- gunakan Exact untuk semantik domain yang sulit diwakili dengan primitive saja
- fokus pada model dan validasi, bukan framework magic

## 11. Contoh domain nyata

```php
<?php

use Exact\Result;

final class OrderService
{
    public function placeOrder(string $customerId, int $amount): Result
    {
        if ($amount <= 0) {
            return Result::err('amount_must_be_positive');
        }

        return Result::ok(['customerId' => $customerId, 'amount' => $amount]);
    }
}
```

## 12. Kesimpulan

Exact membantu anda menulis domain model yang lebih jelas, aman, dan composable. Library ini tidak menghapus PHP, tetapi menambah alat yang lebih mendukung correctness pada model bisnis.
