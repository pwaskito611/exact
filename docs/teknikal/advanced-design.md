# Desain Lanjutan

## 1. Value Object dan NewType

Selain `Option` dan `Result`, Exact juga menyediakan abstraksi untuk membuat type domain yang lebih aman. Tujuan utamanya adalah memastikan value hanya valid pada saat instantiation.

### `ValueObject`

`ValueObject` berperan sebagai base class untuk object dengan data immutable. Class ini menyediakan:

- `equals(self $other)` untuk perbandingan semantik
- `toArray(): array` untuk serialisasi ringan
- hook `validate()` untuk aturan domain

### `NewType`

`NewType` lebih sederhana dan cocok untuk wrap primitive ke domain type. Ia memvalidasi value pada saat `from(...)` dipanggil.

## 2. Pattern matching sederhana

Pattern matching di library dibuat sebagai utility ringan berdasarkan instanceof.

```php
<?php

use Exact\Match;

$result = Match::on($event)
    ->case(OrderPlaced::class, fn ($event) => 'placed')
    ->case(OrderCancelled::class, fn ($event) => 'cancelled')
    ->default(fn () => 'unknown');
```

Desain ini tidak menggantikan native `match` PHP, tetapi memberikan cara yang lebih domain-oriented ketika bekerja dengan object polymorphic.

## 3. Keputusan arsitektur

### Native PHP first

Semua abstraction diusahakan bekerja bersama API native PHP, bukan menggantikan seluruh lingkungan PHP.

### Immutable by default

Value object dan collection dibuat agar object tidak boleh berubah setelah dibuat.

### Explicit semantics

Semantik domain tidak disembunyikan di metode runtime magic. Semua interface dibuat eksplisit dan dapat diprediksi.

## 4. Next enhancements

Fase berikutnya diharapkan mencakup:

- `Tuple` dan `Pair`
- `NonEmptyList`
- lazy collections
- property-based testing
- docs benchmark dan performance notes
