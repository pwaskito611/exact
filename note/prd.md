# PRD — Exact

## 1. Executive Summary

Exact adalah library PHP untuk domain modeling yang terinspirasi dari pendekatan Scala: immutable, explicit, composable, dan correctness-oriented. Library ini dirancang untuk membantu pengembang menulis model bisnis yang lebih kuat tanpa meninggalkan idiom dan ekosistem PHP.

Tujuan utama Exact adalah menyediakan abstraction yang membantu mengurangi primitive obsession, invalid state, dan debugging yang mahal, sambil tetap menjaga agar API terasa natural di PHP. Exact bukan pengganti seluruh paradigma PHP, melainkan lapisan tambahan untuk kebutuhan modeling yang belum ditangani dengan cukup baik oleh bahasa itu sendiri.

Exact fokus pada:

- domain value objects yang eksplisit
- immutable collections dan data structures
- safe handling untuk absence, failure, dan validation
- composition untuk logika domain
- model yang lebih mudah diuji dan dipelihara

---

## 2. Product Name and Positioning

### 2.1 Product Name

Working name: Exact

Nama final dapat ditentukan pada tahap branding selanjutnya.

### 2.2 Positioning Statement

> A Scala-inspired domain modeling library for PHP focused on expressive, immutable, composable, and correctness-oriented domain models.

### 2.3 Product Type

Standalone PHP library, framework-agnostic, dapat digunakan di aplikasi vanilla PHP, Laravel, Symfony, maupun arsitektur backend lain yang tidak menggunakan framework tertentu.

---

## 3. Problem Statement

PHP menyediakan banyak primitive yang kuat seperti class, interface, enum, readonly properties, union types, closures, first-class callables, dan match. Namun, dalam praktik pengembangan domain-heavy application, banyak proyek masih menggunakan pendekatan yang sangat lemah secara semantik:

- null untuk absence
- bool untuk success/failure
- string untuk status
- int untuk semua jenis ID
- array mutable sebagai struktur data domain
- nested conditionals untuk validasi dan error handling

Kondisi tersebut menimbulkan beberapa masalah:

1. Semantik domain tidak eksplisit.
2. State invalid lebih mudah dibuat.
3. Validasi sering tersebar di banyak layer.
4. Testing menjadi lebih kompleks karena nilai domain sulit dipertahankan konsistensinya.
5. Model bisnis sering kehilangan struktur dan kemampuan ekspresi yang dibutuhkan untuk scalability.

Exact hadir untuk menutup gap tersebut dengan menyediakan abstraction yang memadukan konsepsi safety, composability, dan ekspresi domain tanpa menghilangkan feel PHP asli.

---

## 4. Product Goals

### 4.1 Primary Goal

Membuat domain model PHP dapat ditulis dengan cara yang:

- lebih eksplisit
- lebih type-oriented
- lebih immutable
- lebih composable
- lebih mudah divalidasi
- lebih mudah dites
- lebih sulit menghasilkan invalid state
- tetap terasa seperti PHP

### 4.2 Business Goals

- Meningkatkan kualitas dan keterbacaan model domain pada proyek PHP.
- Mengurangi bug yang muncul akibat handling null, error, dan invalid value.
- Membantu developer menyusun domain logic yang lebih prediktif dan maintainable.
- Menjadi pilihan library yang masuk akal bagi developer yang suka model-driven design namun tetap bekerja di ekosistem PHP.

### 4.3 Product Goals

- Menyediakan abstraction yang umum dipakai dalam domain modeling modern.
- Menyediakan API yang jelas, deterministic, dan aman.
- Mengikuti desain native PHP agar library tetap idiomatic.
- Mendukung penggunaan di proyek besar maupun kecil tanpa framework tertentu.

---

## 5. Target Users

### 5.1 Primary Users

- PHP backend developer
- library author
- developer yang membangun domain-heavy applications
- developer yang ingin model urusan bisnis lebih jelas
- engineer yang terbiasa dengan Scala, F#, Kotlin, Haskell, Rust, atau FP concept namun harus bekerja dalam PHP

### 5.2 Secondary Users

- developer Laravel/Symfony
- developer yang ingin mengurangi primitive obsession
- developer yang ingin gaya API functional tanpa membawa framework functional penuh
- tim yang ingin memperkuat boundary antara domain logic dan transport/data layer

### 5.3 Personas

#### Persona A: Domain-focused Backend Engineer

Mengembangkan sistem finansial, logistik, atau e-commerce yang melibatkan banyak aturan bisnis. Ia membutuhkan representasi domain yang jelas, sulit salah pakai, dan mudah dites.

#### Persona B: Library Maintainer

Membangun reusable abstractions untuk aplikasi internal. Ia memerlukan library yang dapat dipadukan dengan PHP native tanpa menambahkan runtime overhead yang buruk atau dependency framework.

#### Persona C: PHP Developer yang Mencintai Functional Style

Menyukai desain dengan `map`, `flatMap`, `match`, dan composition. Ia ingin prinsip tersebut hadir dalam PHP tanpa mengubah seluruh praktik coding ke gaya non-PHP.

---

## 6. User Problems and Needs

Pengguna membutuhkan cara untuk:

- menggantikan null-heavy flow dengan representasi yang semantik
- memodelkan hasil operasi yang bisa gagal secara eksplisit
- memvalidasi data domain tanpa menghamburkan exception di banyak tempat
- memisahkan domain logic dari framework concern
- mengekspos value object yang immutable dan tidak mudah diubah secara tidak valid
- menulis operasi koleksi dengan API yang jelas dan repeatable

Exact harus menjawab kebutuhan ini tanpa mengharuskan pengguna belajar paradigma yang terlalu berat atau migrasi besar-besaran.

---

## 7. Product Principles

### 7.1 Native PHP First

Exact tidak membuat abstraction untuk fitur yang sudah ditangani PHP dengan sangat baik. Library hanya menambahkan lapisan ketika native PHP belum memadai untuk kebutuhan domain modeling.

Contoh:

```php
enum OrderStatus
{
    case Draft;
    case Paid;
    case Cancelled;
}
```

Enum native PHP tidak perlu dibungkus ulang. Exact akan bekerja bersama fitur native tersebut, bukan menggantikannya.

### 7.2 Immutable by Default

Data structure utama harus immutable atau setidaknya mendorong pola immutable. Operasi seperti `map`, `filter`, `append`, atau `with` harus menghasilkan value baru dan tidak mengubah objek lama.

### 7.3 Explicit Over Magic

API harus jelas dan eksplisit. Exact tidak akan menekankan reliance pada runtime magic seperti `__call`, `__get`, proxy, atau reflection yang sulit diprediksi. Jika membutuhkan behavior kompleks, API yang eksplisit akan lebih diutamakan.

### 7.4 Composition Over Framework

Library harus dapat digunakan tanpa Laravel, Symfony, ORM, atau dependency injection container. Ia adalah library domain utility murni.

### 7.5 Correctness Over Convenience

Library harus memilih semantic correctness dan kejelasan daripada syntax paling pendek atau pendekatan yang sangat clever namun sulit dipahami nanti.

### 7.6 Do Not Reimplement PHP

Exact bukan replacement untuk class, enum, readonly, match, iterator, atau callable. Ia merupakan abstraction tambahan yang berinteraksi dengan construct tersebut.

---

## 8. Non-Goals

Pada v1, Exact tidak akan mencakup:

- HTTP framework
- ORM atau database abstraction
- full functional framework
- dependency injection container
- server runtime
- application boilerplate
- generic serialization layer yang ambisius
- template engine

Produk ini fokus pada domain modeling utility, bukan ekosistem penuh.

---

## 9. Functional Requirements

### 9.1 Core Data Types

#### Option

- mewakili nilai yang mungkin ada atau tidak ada
- menghindari penggunaan `null` sebagai representasi domain default
- menyediakan API seperti `map`, `flatMap`, `filter`, `orElse`, `getOrElse`
- mengizinkan pattern matching untuk branch yang jelas

#### Result

- mewakili operasi yang berhasil atau gagal
- membedakan antara data success dan error semantik
- mendorong explicit error handling
- memungkinkan kombinasi error dan validation

#### Either

- mewakili dua kemungkinan outcome yang dibedakan secara tipe
- berguna untuk domain logic yang memerlukan left/right semantics
- cocok untuk pipeline dan composition

#### Validated

- menangani validasi multi-error
- mengumpulkan banyak kesalahan daripada gagal pada error pertama
- sangat cocok untuk domain validation dan business rules

### 9.2 Algebraic Data Types

- menyediakan utilities untuk ADT definition
- mendukung pattern matching berbasis branch
- memberi kemampuan untuk mengekspresikan sum type dan product type di PHP
- memudahkan model seperti `OrderStatus`, `PaymentResult`, `CustomerEvent`, atau `Address`

### 9.3 Value Objects and Newtypes

- support value object yang immutable
- support domain-specific types untuk ID, amount, email, phone number, dll.
- mencegah penggunaan primitive yang tidak terkontrol
- mendukung validation saat instantiation

### 9.4 Immutable Collection

- `List`, `Set`, `Map`, atau koleksi immutable yang serupa
- operasi seperti `map`, `filter`, `reduce`, `flatMap`, `append`, `prepend`
- tidak mengubah objek asal
- dapat dipakai untuk domain pipeline

### 9.5 Functional Composition

- menyediakan API untuk composition ringan
- operasi seperti `pipe`, `compose`, `bind`, `then`
- dapat digunakan bersama `Option`, `Result`, `Either`, dan value object

### 9.6 Pattern Matching

- menyediakan cara yang eksplisit untuk matching ADT atau result
- membantu menghindari nested if/else yang sulit dibaca
- harus tetap kompatibel dengan native `match` PHP ketika relevan

### 9.7 Lazy and Deferred Evaluation

- support lazy collections atau lazy operations bila diperlukan
- memungkinkan composition tanpa eager evaluation
- cocok untuk stream-like atau pipeline domain processing

### 9.8 Documentation and Developer Experience

- docs yang jelas untuk setiap core type
- contoh penggunaan domain nyata
- API reference yang mudah dibaca
- halaman “why this exists” dan “when to use”

---

## 10. Non-Functional Requirements

### 10.1 PHP Compatibility

- library harus kompatibel dengan PHP 8.2+ secara default
- memanfaatkan fitur native seperti enums, readonly, union types, dan match
- tetap kompatibel dengan versi yang didukung oleh ecosystem PHP pada saat rilis v1

### 10.2 Performance

- tidak menambahkan overhead runtime yang berlebihan untuk penggunaan biasa
- operasi primitive harus efisien dan tidak membangun object berlebihan secara tidak perlu
- library harus dapat dipakai di aplikasi latency-sensitive tanpa overhead yang signifikan

### 10.3 Type Safety

- memberi manfaat nyata untuk static analysis
- kode harus terstruktur agar tool seperti PHPStan dan Psalm terbantu
- API harus mendorong type correctness daripada dynamic broad assumptions

### 10.4 Testability

- library harus sangat mudah dites
- setiap core type memiliki test coverage yang tinggi
- behavior yang kompleks seperti validation, composition, dan errors harus di-cover dengan unit test

### 10.5 Maintainability

- API harus mudah dipelajari dan diterapkan
- desain harus konsisten antar type
- naming convention harus mengikuti prinsip semantik yang jelas

---

## 11. User Stories

### 11.1 Domain Modeling

- Sebagai developer, saya ingin value object yang jelas dan immutable agar domain model saya tidak mudah menjadi invalid.
- Sebagai developer, saya ingin `Option` dan `Result` agar saya tidak perlu bergantung pada `null` dan `bool` untuk representasi hasil.

### 11.2 Validation

- Sebagai developer, saya ingin validasi multi-error agar saya dapat melihat seluruh masalah sekaligus, bukan satu error per request.
- Sebagai developer, saya ingin domain rule di satu tempat agar validasi tidak tercecer di controller/service.

### 11.3 Collection Logic

- Sebagai developer, saya ingin data collection yang tetap immutable agar operasi transformasi tidak menimbulkan side effect yang tidak terduga.
- Sebagai developer, saya ingin pipeline transformasi yang jelas dan mudah dibaca untuk proses bisnis.

### 11.4 Composition

- Sebagai developer, saya ingin hasil operasi dapat di-chain dengan cara yang aman dan eksplisit.
- Sebagai developer, saya ingin logika bisnis yang dapat dibangun dari banyak fungsi kecil yang mudah diuji.

---

## 12. Functional API Proposal

### 12.1 Example: Option

```php
use Exact\Option;

$email = Option::from($user?->email);

$result = $email
    ->map(fn (string $email) => strtolower($email))
    ->filter(fn (string $email) => str_contains($email, '@'))
    ->getOrElse('unknown@example.com');
```

### 12.2 Example: Result

```php
use Exact\Result;

$result = Result::ok($order)
    ->flatMap(fn ($order) => $this->validateOrder($order))
    ->map(fn ($order) => $order->markPaid())
    ->onFailure(fn ($error) => $this->logger->error($error->message()));
```

### 12.3 Example: Validated

```php
use Exact\Validated;

$validated = Validated::success(new CustomerId('abc'))
    ->combine(Validated::fail(['email' => 'invalid email']));
```

### 12.4 Example: ADT Pattern Matching

```php
use Exact\Match;

$event = new OrderPlaced($orderId);

$result = Match::on($event)
    ->case(OrderPlaced::class, fn ($evt) => 'placed')
    ->case(OrderCancelled::class, fn ($evt) => 'cancelled')
    ->default(fn () => 'unknown');
```

---

## 13. Scope Definition

### 13.1 v1 Scope

- `Option`
- `Result`
- `Either`
- `Validated`
- ADT utilities
- value object abstraction
- domain-type/newtype support
- immutable collections
- functional combinators
- docs and examples
- high test coverage
- static analysis support

### 13.2 Future Scope

- `Tuple` and `Pair`
- `NonEmptyList`
- property-based testing
- mutation testing
- benchmark tools
- richer lazy collection support
- advanced function abstraction
- effect abstraction

---

## 14. Release Plan

### v0.1 — Foundation

- core primitives: `Option`, `Result`, `Either`
- basic immutable collection support
- initial docs and examples
- tests for basic behaviors

### v0.2 — Validation and ADT

- `Validated`
- ADT utilities
- pattern matching design
- value object support

### v0.3 — Domain Ergonomics

- newtype support
- lazy collection operations
- advanced composition APIs
- broader examples and docs

### v1.0 — Stable Release

- production-ready API
- strong test coverage
- stable semver contract
- static analysis support
- mature docs and examples

---

## 15. Success Metrics

Exact dianggap sukses jika:

- library mudah dipahami oleh developer PHP dengan pengalaman backend biasa
- API dan docs cukup jelas untuk digunakan tanpa tutorial panjang
- developer dapat menulis domain model yang lebih eksplisit dengan sedikit ceremony
- library membantu mengurangi bug akibat null, invalid state, dan unstructured validation
- open source adoption atau internal adoption menunjukkan nilai nyata pada project domain-heavy

Metrics yang dapat diukur:

- number of example use cases implemented by early adopters
- issue resolution speed
- number of supported apps using Exact in real projects
- user feedback terkait readability and correctness improvements

---

## 16. Risks and Mitigations

### 16.1 Risk: API feels too abstract or too Scala-like

Mitigation:
- tetap menjaga API terasa seperti PHP, bukan gaya fully functional yang alien
- prioritas desain native PHP first
- sangat hati-hati dalam naming dan ergonomics

### 16.2 Risk: Developer confusion around immutability and performance

Mitigation:
- dokumentasikan tradeoff secara jelas
- menegaskan bahwa immutability bukan mutlak wajib untuk semua kasus
- fokus pada value object dan domain structures yang memang benefit besar

### 16.3 Risk: Over-engineering

Mitigation:
- prioritaskan core abstraction v1 yang benar-benar essential
- limit feature scope agar library tidak kehilangan fokus

### 16.4 Risk: Static analysis friction

Mitigation:
- lib harus dirancang dengan type safety yang masuk akal
- contoh, docs, dan generic usage harus konsisten

---

## 17. Constraints

- harus dapat dipakai tanpa framework tertentu
- harus bekerja dalam ekosistem PHP modern
- harus menjaga API yang jelas dan maintainable
- tidak boleh menggantikan primitive PHP secara paksa
- harus cocok untuk proyek domain-heavy tetapi tetap sederhana untuk proyek kecil

---

## 18. Acceptance Criteria

### 18.1 Core Functionality

- `Option` dapat merepresentasikan presence/absence tanpa null-heavy flow.
- `Result` dapat mengekspresikan success/failure secara eksplisit.
- `Either` dapat menangani left/right semantics secara jelas.
- `Validated` dapat mengumpulkan lebih dari satu error validasi.
- Immutable collection dapat melakukan transformasi tanpa mutasi state sumber.

### 18.2 Developer Experience

- contoh penggunaan jelas dan mudah dipahami
- dokumentasi menjelaskan kapan menggunakan `Option`, `Result`, `Either`, atau `Validated`
- API membersihkan domain logic dibanding versi primitive-only

### 18.3 Quality Bar

- high unit test coverage pada semua core type
- naming dan semantics konsisten
- support static analysis tanpa banyak warning yang tidak perlu

---

## 19. Final Recommendation

Exact sebaiknya dibangun sebagai library domain modeling yang ringan, eksplisit, dan framework-agnostic. Fokus utama adalah memberi PHP developer alat untuk menulis model domain yang lebih aman dan lebih jelas tanpa mengorbankan idiom native PHP.

Keputusan desain yang paling penting adalah:

1. gunakan PHP native first
2. pilih immutable dan explicit semantics
3. prioritaskan correctness atas cleverness
4. fokus pada abstraction yang benar-benar menambah nilai
5. hindari menjadikan library ini sebagai framework yang besar dan berlebihan

Dengan pendekatan itu, Exact dapat menjadi alat yang bermanfaat bagi tim PHP yang ingin membangun model bisnis yang lebih kuat, lebih testable, dan lebih mudah dipelihara.

* dependency injection
* event bus
* actor system
* async runtime
* database abstraction
* application container
* custom programming language syntax
* compiler
* full Scala type system
* full Haskell type system

---

# 5. High-Level Architecture

```text
PHP Native Features
        │
        ├── enum
        ├── readonly class
        ├── class
        ├── callable
        ├── match
        ├── Iterator
        └── type system
        │
        ↓
Modeling Library
        │
        ├── Data
        │   ├── Option
        │   ├── Result
        │   ├── Either
        │   ├── Validated
        │   └── Tuple
        │
        ├── ADT
        │   ├── Variant
        │   └── Match
        │
        ├── Value
        │   ├── ValueObject
        │   └── Newtype utilities
        │
        ├── Collection
        │   ├── List
        │   ├── NonEmptyList
        │   ├── Map
        │   ├── Set
        │   └── LazyList
        │
        └── Function
            ├── Pipe
            └── Composition
```

---

# 6. Repository Architecture

```text
project/
├── src/
│   ├── Data/
│   │   ├── Option/
│   │   │   ├── Option.php
│   │   │   ├── Some.php
│   │   │   └── None.php
│   │   │
│   │   ├── Result/
│   │   │   ├── Result.php
│   │   │   ├── Ok.php
│   │   │   └── Err.php
│   │   │
│   │   ├── Either/
│   │   │   ├── Either.php
│   │   │   ├── Left.php
│   │   │   └── Right.php
│   │   │
│   │   ├── Validated/
│   │   │   ├── Validated.php
│   │   │   ├── Valid.php
│   │   │   └── Invalid.php
│   │   │
│   │   └── Tuple/
│   │       ├── Tuple.php
│   │       └── Pair.php
│   │
│   ├── Adt/
│   │   ├── Variant.php
│   │   ├── Match.php
│   │   └── Matcher.php
│   │
│   ├── Value/
│   │   ├── ValueObject.php
│   │   └── Newtype.php
│   │
│   ├── Collection/
│   │   ├── List.php
│   │   ├── NonEmptyList.php
│   │   ├── Map.php
│   │   ├── Set.php
│   │   └── LazyList.php
│   │
│   ├── Function/
│   │   ├── Fn.php
│   │   ├── Pipe.php
│   │   └── Composition.php
│   │
│   └── Exception/
│       ├── MatchException.php
│       ├── EmptyCollectionException.php
│       └── InvalidStateException.php
│
├── tests/
│   ├── Unit/
│   │   ├── Data/
│   │   ├── Adt/
│   │   ├── Value/
│   │   ├── Collection/
│   │   └── Function/
│   │
│   ├── Integration/
│   │   ├── DomainModeling/
│   │   └── Composition/
│   │
│   ├── Property/
│   │   ├── Data/
│   │   ├── Collection/
│   │   └── Algebra/
│   │
│   ├── Fixtures/
│   └── Support/
│
├── docs/
│   ├── usage/
│   │   ├── README.md
│   │   ├── installation.md
│   │   ├── getting-started.md
│   │   ├── option.md
│   │   ├── result.md
│   │   ├── either.md
│   │   ├── validated.md
│   │   ├── adt.md
│   │   ├── pattern-matching.md
│   │   ├── value-objects.md
│   │   ├── newtypes.md
│   │   ├── tuples.md
│   │   ├── collections.md
│   │   ├── lazy-collections.md
│   │   ├── function-composition.md
│   │   ├── error-handling.md
│   │   ├── domain-modeling.md
│   │   └── examples/
│   │       ├── ecommerce.md
│   │       ├── payment.md
│   │       └── registration.md
│   │
│   └── technical/
│       ├── README.md
│       ├── architecture.md
│       ├── design-principles.md
│       ├── type-model.md
│       ├── adt-design.md
│       ├── option-design.md
│       ├── result-design.md
│       ├── collection-design.md
│       ├── immutability.md
│       ├── composition.md
│       ├── error-semantics.md
│       ├── performance.md
│       ├── testing-strategy.md
│       ├── property-testing.md
│       ├── mutation-testing.md
│       ├── static-analysis.md
│       ├── php-compatibility.md
│       ├── api-stability.md
│       └── decisions/
│           ├── README.md
│           ├── 001-native-enums.md
│           ├── 002-no-framework-dependency.md
│           ├── 003-immutable-by-default.md
│           ├── 004-result-vs-exception.md
│           └── 005-no-runtime-reflection.md
│
├── examples/
│   ├── basic/
│   ├── domain/
│   └── advanced/
│
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── infection.json5
├── php-cs-fixer.php
├── README.md
├── CHANGELOG.md
└── LICENSE
```

---

# 7. Module Specifications

## 7.1 Option

### Purpose

Represent optional values explicitly.

```text
Option<T>
├── Some<T>
└── None
```

### Construction

```php
Option::some($value);
Option::none();
```

### Required API

```text
isDefined()
isEmpty()
map()
flatMap()
filter()
fold()
getOrElse()
orElse()
foreach()
```

### Semantics

`Some<T>`:

```text
isDefined = true
map(f) = Some(f(value))
```

`None`:

```text
isDefined = false
map(f) = None
```

### Tests

* Some stores value
* None stores no value
* map
* flatMap
* filter
* fold
* fallback behavior
* no mutation
* identity law
* composition law

---

# 8. Result

## Purpose

Represent an operation that can succeed or fail.

```text
Result<T,E>
├── Ok<T>
└── Err<E>
```

### Construction

```php
Result::ok($value);
Result::err($error);
```

### Required API

```text
isOk()
isErr()
map()
mapError()
flatMap()
fold()
recover()
recoverWith()
getOrElse()
orElse()
```

### Semantics

```text
Ok.map(f)  → Ok(f(value))
Err.map(f) → Err(error)
```

### Error Philosophy

Expected domain failures should use `Result`.

Programmer errors and broken invariants may use exceptions.

---

# 9. Either

## Purpose

Provide a general two-branch data type.

```text
Either<L,R>
├── Left<L>
└── Right<R>
```

### API

```text
left()
right()
map()
mapLeft()
flatMap()
fold()
swap()
```

---

# 10. Validated

## Purpose

Represent validation where multiple failures can be accumulated.

```text
Validated<T,E>
├── Valid<T>
└── Invalid<List<E>>
```

### Example

```text
Input
 ├── name   → invalid
 ├── email  → invalid
 └── age    → invalid

Validated
 └── Invalid([
       NameError,
       EmailError,
       AgeError
    ])
```

### Required behavior

Unlike `Result`, validation should not stop at the first error when combining independent validations.

---

# 11. Tuple

Provide immutable tuples for small product types.

```php
Pair::of($a, $b);

Tuple::of($a, $b, $c);
```

### API

```text
get(index)
first()
second()
map()
toArray()
```

No arbitrary mutation.

---

# 12. Algebraic Data Types

## Objective

Provide practical support for Scala-like sum types.

Example conceptual model:

```text
Payment
├── Pending
├── Paid(TransactionId)
└── Failed(PaymentError)
```

PHP implementation should favor normal PHP classes:

```php
abstract readonly class Payment
{
}
```

with:

```php
final readonly class Pending extends Payment
{
}
```

```php
final readonly class Paid extends Payment
{
    public function __construct(
        public TransactionId $transactionId
    ) {}
}
```

```php
final readonly class Failed extends Payment
{
    public function __construct(
        public PaymentError $error
    ) {}
}
```

The library should provide utilities around this model rather than hiding the actual PHP classes.

---

# 13. Pattern Matching

### API

```php
Match::on($value)
    ->case(Pending::class, fn (Pending $value) => ...)
    ->case(Paid::class, fn (Paid $value) => ...)
    ->case(Failed::class, fn (Failed $value) => ...)
    ->run();
```

### Requirements

* class matching
* enum matching where practical
* default branch
* runtime exhaustive checking
* nested matching support
* readable failure message
* no silent unmatched case

### Important limitation

PHP cannot provide Scala-level compile-time exhaustive checking through an ordinary runtime library.

The library must therefore describe its guarantee accurately as **runtime exhaustive matching**.

---

# 14. Value Objects

Value objects should:

* be immutable
* encapsulate invariants
* provide semantic operations
* avoid public mutable state

Example:

```php
final readonly class Email
{
    private function __construct(
        private string $value
    ) {}

    public static function parse(string $value): Result
    {
        // validation
    }

    public function value(): string
    {
        return $this->value;
    }
}
```

---

# 15. Smart Constructors

A smart constructor should prevent invalid values from entering a domain model.

Example:

```php
Email::parse($input);
```

instead of:

```php
new Email($input);
```

when validation is required.

Possible result:

```text
Result<Email, InvalidEmail>
```

---

# 16. Newtypes

Purpose:

Prevent accidental interchangeability of primitives.

Bad:

```php
function findUser(int $id)
```

Potentially accepts:

```text
UserId
OrderId
ProductId
```

Good:

```php
function findUser(UserId $id)
```

The library may provide lightweight helpers, but explicit PHP classes remain the recommended approach when maximum clarity is desired.

---

# 17. Immutable Collections

## List

Required API:

```text
map
filter
flatMap
fold
reduce
find
exists
forall
take
drop
head
tail
partition
groupBy
zip
reverse
sort
count
isEmpty
toArray
```

## NonEmptyList

Guarantees at least one element.

Required API should allow operations such as:

```text
head()
tail()
map()
fold()
```

without representing `head()` as optional.

## Set

Guarantees uniqueness.

Required API:

```text
add
remove
contains
union
intersect
difference
map
filter
```

## Map

Required API:

```text
get
contains
put
remove
map
filter
keys
values
```

All collection types must preserve immutable semantics.

---

# 18. Lazy Collections

Provide a lazy sequence abstraction.

Example:

```php
LazyList::from($iterator)
    ->map(...)
    ->filter(...)
    ->take(100)
    ->toList();
```

### Requirements

* lazy map
* lazy filter
* lazy flatMap
* lazy take
* lazy drop
* terminal operation
* no unnecessary intermediate materialization

### Tests

Callbacks must not execute before consumption.

---

# 19. Function Composition

Provide minimal functional utilities.

### Pipe

```php
Pipe::of($value)
    ->through(trim(...))
    ->through(strtolower(...))
    ->through(normalize(...));
```

### Composition

```php
$normalize =
    Fn::of(trim(...))
        ->andThen(strtolower(...))
        ->andThen(normalize(...));
```

Required:

```text
compose
andThen
pipe
identity
```

Avoid building a complex custom function type system unless required by later use cases.

---

# 20. Error Semantics

The library must establish clear semantic boundaries.

```text
Option
    ↓
absence

Result
    ↓
expected success/failure

Validated
    ↓
multiple independent validation failures

Exception
    ↓
programmer/system/unexpected failure
```

The documentation must explicitly discourage using `Result` for every possible exception.

---

# 21. Testing Architecture

## 21.1 Unit Tests

Every public type must have unit tests.

Example:

```text
tests/Unit/Data/Option/
tests/Unit/Data/Result/
tests/Unit/Data/Either/
tests/Unit/Data/Validated/
tests/Unit/Adt/
tests/Unit/Value/
tests/Unit/Collection/
tests/Unit/Function/
```

Tests must verify behavior rather than implementation details.

---

# 22. Property-Based Testing

Property tests must cover algebraic behavior.

### Option

```text
map(identity(x)) == identity(Option(x))
```

```text
map(f).map(g) == map(g ∘ f)
```

### Result

```text
Ok(x).map(identity) == Ok(x)
```

```text
Err(e).map(f) == Err(e)
```

### Collections

```text
map(identity, xs) == xs
```

```text
length(map(f, xs)) == length(xs)
```

### Set

```text
add(x, add(x, S)) == add(x, S)
```

---

# 23. Immutability Tests

Every immutable structure must be tested for preservation of original state.

Example:

```php
$original = List::of(1, 2, 3);

$new = $original->map(
    fn (int $value): int => $value * 2
);
```

Expected:

```text
original = [1, 2, 3]
new      = [2, 4, 6]
```

No operation may mutate `$original`.

---

# 24. Integration Tests

Create realistic domain examples.

## Payment domain

```text
Payment
 ├── Pending
 ├── Paid
 └── Failed
```

Combine:

```text
ADT
+
Match
+
Result
+
Option
+
ValueObject
```

## User registration

Combine:

```text
Email
UserId
Validated
Result
Option
```

## E-commerce

Combine:

```text
Money
ProductId
Order
List
Result
ADT
```

These examples ensure the abstractions work together rather than only working independently.

---

# 25. Mutation Testing

Use Infection.

Mutation testing should be applied to core modules:

```text
Option
Result
Either
Validated
ADT
Collections
```

The goal is not necessarily 100% mutation score, but mutation survivors must be reviewed.

Particularly important mutations:

* conditional inversion
* return value changes
* removed method calls
* comparison changes
* boolean changes
* boundary changes

---

# 26. Static Analysis

Primary tool:

**PHPStan**

Target:

```text
maximum practical level
```

The project must maintain strict type annotations.

Generic-like types should be documented through PHPDoc where PHP itself cannot express them.

Example conceptual type:

```text
Option<T>
Result<T,E>
List<T>
Either<L,R>
```

Static analysis should be treated as a core correctness mechanism, not merely a linting step.

---

# 27. Coding Standards

Use:

* PSR-4 autoloading
* PSR-compatible conventions
* strict types
* explicit return types
* explicit parameter types
* readonly where appropriate
* final classes where inheritance is not part of the API

Prefer:

```php
declare(strict_types=1);
```

in source files.

Avoid:

* hidden global state
* mutable static state
* unnecessary inheritance
* unnecessary interfaces
* magic methods
* reflection-heavy implementations

---

# 28. Performance

Performance requirements:

1. Do not use reflection on hot paths.
2. Do not serialize objects to implement normal operations.
3. Do not use dynamic proxies unnecessarily.
4. Lazy collections must remain lazy.
5. Collection behavior must be benchmarked.
6. Allocation overhead must be measured.

Benchmarks should compare:

```text
Native array
vs
List
```

for:

* creation
* iteration
* map
* filter
* reduction

and:

```text
eager collection
vs
LazyList
```

for large datasets.

Performance results must be documented rather than hidden.

---

# 29. Documentation Architecture

Documentation must be split into exactly two primary sections:

```text
docs/
├── usage/
└── technical/
```

## Usage Documentation

Audience:

> library users

Purpose:

> explain how to use the library.

Required documents:

```text
installation.md
getting-started.md
option.md
result.md
either.md
validated.md
adt.md
pattern-matching.md
value-objects.md
newtypes.md
tuples.md
collections.md
lazy-collections.md
function-composition.md
error-handling.md
domain-modeling.md
```

Usage documentation must prioritize practical examples.

---

## Technical Documentation

Audience:

> maintainers and contributors

Required:

```text
architecture.md
design-principles.md
type-model.md
adt-design.md
option-design.md
result-design.md
collection-design.md
immutability.md
composition.md
error-semantics.md
performance.md
testing-strategy.md
property-testing.md
mutation-testing.md
static-analysis.md
php-compatibility.md
api-stability.md
```

Technical documentation explains implementation decisions and constraints.

---

# 30. Architecture Decision Records

Directory:

```text
docs/technical/decisions/
```

Required initial ADRs:

## ADR-001 — Native PHP Enums

Decision:

Do not wrap PHP enums by default.

Reason:

PHP enums already provide an adequate finite-value abstraction.

The library should only add utilities where they provide additional semantic value.

---

## ADR-002 — No Framework Dependency

Decision:

The library must remain framework-independent.

---

## ADR-003 — Immutable by Default

Decision:

Core data structures are immutable.

---

## ADR-004 — Result vs Exception

Decision:

Expected domain failures use `Result`.

Unexpected/programmer/system failures may use exceptions.

---

## ADR-005 — Avoid Runtime Reflection

Decision:

Reflection must not be a fundamental mechanism of the library.

---

# 31. Package Dependencies

Production dependencies should be minimized.

Ideal:

```text
Production dependencies:
0
```

Development dependencies may include:

```text
PHPUnit
PHPStan
Infection
PHP-CS-Fixer
```

Optional property-testing tooling may be introduced if it provides sufficient value.

---

# 32. PHP Version

Initial target:

```text
PHP >= 8.3
```

The exact minimum version may be adjusted during implementation based on required language features.

The project should not support old PHP versions if doing so substantially complicates the design.

---

# 33. API Stability

Public API must be deliberately small.

Before releasing v1:

* review every public class
* review every public method
* remove experimental abstractions
* mark unstable components explicitly
* avoid exposing internal implementation classes

Internal implementation must remain replaceable.

---

# 34. Versioning

Use Semantic Versioning:

```text
MAJOR.MINOR.PATCH
```

Examples:

```text
1.0.0
1.1.0
1.1.1
2.0.0
```

Breaking public API changes require a major release.

---

# 35. Development Phases

## Phase 0 — Foundation

Implement:

```text
Composer
PSR-4
strict types
PHPUnit
PHPStan
CS fixer
CI
basic documentation
```

Definition of done:

* project installs
* tests execute
* static analysis passes
* CI works

---

## Phase 1 — Core Data Types

Implement:

```text
Option
Result
Either
Tuple
```

Requirements:

* immutable
* complete unit tests
* property tests
* documentation

---

## Phase 2 — Domain Modeling

Implement:

```text
ValueObject utilities
Newtype utilities
ADT
Match
```

This phase establishes the primary identity of the library.

---

## Phase 3 — Collections

Implement:

```text
List
NonEmptyList
Map
Set
```

Requirements:

* immutable
* composable
* tested
* benchmarked

---

## Phase 4 — Functional Composition

Implement:

```text
Pipe
Composition
LazyList
```

---

## Phase 5 — Validation

Implement:

```text
Validated
```

with error accumulation.

---

## Phase 6 — Hardening

Implement:

```text
Property testing
Mutation testing
Benchmarks
Static analysis hardening
API review
Documentation review
```

---

# 36. Example End-to-End Usage

The final API should allow code conceptually similar to:

```php
$result = Email::parse($input['email'])
    ->flatMap(
        fn (Email $email) =>
            UserId::parse($input['user_id'])
                ->map(
                    fn (UserId $id) =>
                        User::create($id, $email)
                )
    );
```

ADT:

```php
Match::on($payment)
    ->case(
        Pending::class,
        fn (Pending $payment) => 'Waiting for payment'
    )
    ->case(
        Paid::class,
        fn (Paid $payment) =>
            "Paid: {$payment->transactionId->value()}"
    )
    ->case(
        Failed::class,
        fn (Failed $payment) =>
            "Failed: {$payment->error->message()}"
    )
    ->run();
```

Collection:

```php
$revenue = List::of(...$orders)
    ->filter(
        fn (Order $order): bool => $order->isPaid()
    )
    ->map(
        fn (Order $order): Money => $order->total()
    )
    ->fold(
        Money::zero(),
        fn (Money $a, Money $b): Money => $a->add($b)
    );
```

---

# 37. Quality Gates

A pull request cannot be merged unless:

```text
✓ Unit tests pass
✓ Integration tests pass
✓ Property tests pass
✓ Static analysis passes
✓ Coding standard passes
✓ No unintended public API changes
✓ Relevant documentation updated
```

For core behavior:

```text
✓ Mutation testing reviewed
```

For performance-sensitive changes:

```text
✓ Benchmark reviewed
```

---

# 38. Definition of Done — v1.0

The project is ready for v1.0 when all conditions below are satisfied.

### Core

* Option complete
* Result complete
* Either complete
* Validated complete
* Tuple complete
* ADT support complete
* pattern matching complete
* immutable Value Objects supported
* newtype/domain modeling supported
* List complete
* NonEmptyList complete
* Map complete
* Set complete
* LazyList complete
* function composition complete

### Correctness

* unit tests complete
* integration tests complete
* property tests cover core algebra
* immutability verified
* mutation testing performed
* PHPStan passes

### Documentation

```text
docs/usage/
```

contains complete user documentation.

```text
docs/technical/
```

contains architecture and contributor documentation.

### Engineering

* CI works
* Composer package works
* API reviewed
* benchmarks available
* no unnecessary framework dependencies
* no unexplained runtime magic
* no known critical correctness issues

---

# 39. Explicit Non-Goals for Future Scope

The following should not be added merely because they are present in Scala or functional languages:

```text
Cats-style ecosystem
full effect system
IO runtime
actor model
async scheduler
type-level programming framework
custom compiler
macro system
dependency injection
ORM
web framework
application framework
```

Any future feature must justify itself against the core product goal:

> Does this materially improve expressive and correct domain modeling in PHP?

If not, it should remain outside the library.

---

# 40. Final Product Architecture

```text
                           PHP
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
       Types              Runtime            Syntax
        │                   │                   │
 enum / readonly       Iterator / Closure      match
        │                   │                   │
        └───────────────────┼───────────────────┘
                            ↓
                    MODELING LIBRARY
                            │
       ┌────────────────────┼────────────────────┐
       │                    │                    │
       ↓                    ↓                    ↓
     DATA                  DOMAIN            COLLECTION
       │                    │                    │
 Option                  ValueObject            List
 Result                  Newtype                Map
 Either                  ADT                    Set
 Validated               Match                  NEL
 Tuple                                           LazyList
       │                    │                    │
       └────────────────────┼────────────────────┘
                            ↓
                     COMPOSITION
                            │
                  Pipe / Function / Map
                            │
                            ↓
                    DOMAIN APPLICATION
```

## Core Philosophy

```text
PHP native feature
        ↓
use it directly if sufficient
        ↓
library abstraction only when necessary
        ↓
make domain semantics explicit
        ↓
prefer immutable values
        ↓
compose operations
        ↓
make invalid states harder to represent
```

**Success criterion utama bukan membuat PHP terlihat seperti Scala.**

Success criterion-nya adalah:

> **Developer dapat memodelkan domain yang kompleks di PHP dengan API yang lebih eksplisit, immutable, composable, dan correctness-oriented, tanpa membutuhkan framework atau abstraction yang tidak perlu.**
