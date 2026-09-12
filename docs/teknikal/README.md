# Dokumentasi Teknis

## 1. Tujuan dokumentasi

Dokumen ini menjelaskan arsitektur, pola desain, dan keputusan teknis dari library Exact. Fokus utama adalah menjelaskan bagaimana library dibangun agar tetap sesuai dengan prinsip PRD: native PHP first, immutable by default, explicit over magic, dan correctness over convenience.

## 2. Arsitektur umum

Exact dibangun sebagai library framework-agnostic dengan dua layer utama:

1. core domain abstractions
2. lightweight immutable collection utilities

Struktur dasar:

```text
src/
  Collections/
    ImmutableList.php
  Either.php
  Option.php
  Result.php
  Validated.php
```

### Prinsip desain

- semua type utama dibuat sebagai object value yang jelas
- operasi transformasi mengembalikan instance baru, bukan memodifikasi state lama
- API diusahakan eksplisit dan mudah diprediksi
- library tidak bergantung pada framework atau runtime magic

## 3. Core types

### 3.1 Option

`Option` merepresentasikan kemungkinan hadir atau tidak hadir.

#### State

- `Some(value)`
- `None()`

#### Operasi inti

- `map(callable)`
- `flatMap(callable)`
- `filter(callable)`
- `getOrElse(mixed $default)`
- `orElse(self $default)`

#### Implementasi catatan

`Option` dibuat menjadi final class dengan private constructor. Ini memastikan semua instance dibuat melalui factory dan menghilangkan state mutation yang tidak sesuai.

## 4. Result

`Result` merepresentasikan success/failure domain outcome.

#### State

- `Ok(value)`
- `Err(error)`

#### Operasi inti

- `map(callable)`
- `flatMap(callable)`
- `onFailure(callable)`
- `getOrElse(mixed $default)`

#### Keputusan desain

`Result` digunakan untuk operasi yang hasilnya bisa gagal tetapi tetap memegang semantik yang jelas. Tidak seperti `bool`, `Result` membawa payload value dan error secara eksplisit.

## 5. Either

`Either` adalah dua-branch type yang umum digunakan untuk left/right semantics.

#### State

- `Left(value)`
- `Right(value)`

#### Operasi inti

- `map(callable)`
- `flatMap(callable)`
- `mapLeft(callable)`
- `getOrElse(mixed $default)`

#### Kapan dipakai

- bila domain memiliki semantik left/right yang jelas
- bila perlu menekankan arah keputusan atau domain branch

## 6. Validated

`Validated` digunakan untuk validasi multi-error.

### Karakteristik

- `success(value)` untuk valid data
- `fail(array $errors)` untuk invalid data
- `combine(self $other)` menggabungkan error dari beberapa validasi

### Mengapa penting

Pada domain nyata, validasi sering melibatkan lebih dari satu masalah. `Validated` memungkinkan pengumpulan error sekaligus dan mereduksi friction dalam flow validation.

## 7. ImmutableList

`ImmutableList` adalah koleksi immutable ringan.

### Fitur

- `fromArray(array)`
- `toArray(): array`
- `map(callable)`
- `filter(callable)`
- `append(mixed)`
- `count(): int`

### Prinsip

Setiap operasi baru menciptakan instance baru dan tidak mengubah data lama.

## 8. Type system strategy

Exact memanfaatkan fitur native PHP 8.2+ seperti:

- readonly properties
- union types
- typed parameters
- enums
- match (ketika relevan)

Library tetap mengikuti style native PHP tanpa mencoba membangun abstraction berlebihan.

## 9. Testing strategy

Project menggunakan PHPUnit dan memprioritaskan coverage untuk behavior inti.

### Fokus test

- validasi null/none
- map dan flatMap behavior
- failure handling
- immutability guarantee
- validation aggregation

## 10. Good practices untuk prototyping

- gunakan factory method untuk pembuatan object
- hindari `__call` atau reflection untuk fitur utama
- desain method agar jelas dan deterministik
- unit test behavior domain yang penting

## 11. Roadmap teknis berikutnya

### Fase 1

- `Either` dan `Validated` finalisasi API
- improvement pada docs dan examples
- test tambahan untuk edge cases

### Fase 2

- `ValueObject` base abstraction
- `NewType` support
- `Tuple`/`Pair`
- `NonEmptyList`

### Fase 3

- lazy collections
- pattern matching utilities
- property-based testing

## 12. Kesimpulan

Exact dirancang sebagai library yang aman, eksplisit, dan gampang dipakai di ekosistem PHP. Arsitekturnya tetap kecil, modular, dan fokus pada domain modeling instead of framework magic.

Keberhasilan library ini akan ditentukan oleh seberapa besar API-nya dapat menyederhanakan model bisnis tanpa membuat developer merasa "terpaksa" berpindah ke paradigma baru.
