# Collections, Pair, Tuple, dan NonEmptyList

## 1. Tujuan

Exact menyediakan beberapa abstraction kecil untuk data yang lebih ekspresif dibanding array mentah.

## 2. Pair

Pair adalah container untuk dua nilai.

```php
<?php

use Exact\Pair;

$pair = Pair::of('nama', 'alice');

var_dump($pair->first());
var_dump($pair->second());
var_dump($pair->swap()->toArray());
```

Kegunaan umum:

- key-value ringan
- hasil dua nilai yang terkait
- transformasi data dua arah

## 3. Tuple

Tuple membantu menyimpan banyak nilai dengan index yang jelas.

```php
<?php

use Exact\Tuple;

$tuple = Tuple::of('id', 42, true);

var_dump($tuple->at(0));
var_dump($tuple->count());
var_dump($tuple->toArray());
```

## 4. NonEmptyList

NonEmptyList menjamin list tidak kosong.

```php
<?php

use Exact\NonEmptyList;

$list = NonEmptyList::of('a', 'b', 'c');

var_dump($list->head());
var_dump($list->tail());
```

Ini berguna untuk domain data yang selalu memiliki minimal satu item, misalnya `OrderItems`, `Tags`, atau `Participants`.

## 5. Praktik bagus

- gunakan `Pair` untuk hubungan dua nilai yang tetap
- gunakan `Tuple` untuk kelompok data dengan urutan tetap
- gunakan `NonEmptyList` bila domain menegaskan data tidak boleh kosong
- hindari array mentah untuk data yang punya semantik domain kuat
