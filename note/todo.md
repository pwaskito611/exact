| Urutan | Tipe              | Alasan                               |
| -----: | ----------------- | ------------------------------------ |
|      1 | `Exception`       | Infrastruktur error paling dasar     |
|      2 | `ValueObject`     | Fondasi domain value yang immutable  |
|      3 | `Newtype`         | Spesialisasi value/domain type       |
|      4 | `Tuple`           | Product type sederhana               |
|      5 | `Option`          | Fondasi absence handling             |
|      6 | `Either`          | Fondasi two-branch computation       |
|      7 | `Result`          | Error handling berbasis `Either`/ADT |
|      8 | `Validated`       | Validasi dengan error accumulation   |
|      9 | `ADT / Variant`   | Fondasi sum type/domain variant      |
|     10 | `Match / Matcher` | Pattern matching atas ADT            |
|     11 | `List`            | Collection fundamental               |
|     12 | `NonEmptyList`    | `List` dengan invariant non-empty    |
|     13 | `Set`             | Immutable unique collection          |
|     14 | `Map`             | Immutable key-value collection       |
|     15 | `LazyList`        | Lazy computation di atas collection  |
|     16 | `Fn`              | Functional function abstraction      |
|     17 | `Composition`     | Function composition                 |
|     18 | `Pipe`            | Data-flow composition                |
