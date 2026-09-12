# Koleksi dan Struktur Data Domain

## 1. Tujuan

Exact menyediakan beberapa struktur kecil untuk data yang semantiknya lebih jelas daripada array generic. Ini membantu domain logic tetap jelas, immutable, dan mudah diuji.

## 2. Pair

`Pair` adalah value object untuk dua elemen. Ia dibuat dengan factory `Pair::of($first, $second)` dan menyimpan nilai yang immutable.

### API utama

- `first(): mixed`
- `second(): mixed`
- `swap(): self`
- `toArray(): array`

### Contoh use case

- lokasi geografis `(lat, lng)`
- pasangan label dan nilai
- data hasil transformasi dua variabel

## 3. Tuple

`Tuple` dapat menampung n nilai dan menjaga urutan di dalamnya. Ia tidak sepenuhnya menggantikan array, tetapi berguna untuk data composite yang punya maksud tertentu.

### API utama

- `of(...$values)`
- `at(int $index): mixed`
- `count(): int`
- `map(callable $fn): self`
- `toArray(): array`

### Catatan desain

Tuple ini dibuat ringan, eksplisit, dan tidak berlebihan. Tujuannya bukan membuat abstraction yang terlalu rumit, melainkan memberi nilai semantik pada grup data.

## 4. NonEmptyList

NonEmptyList adalah representasi list yang secara type-level menjamin ada minimal satu item. Hal ini sangat berguna untuk domain yang tak boleh kosong.

### API utama

- `fromArray(array $items)`
- `of(mixed $head, mixed ...$tail)`
- `head(): mixed`
- `tail(): array`
- `append(mixed $value): self`
- `map(callable $fn): self`

### Alasan penting

Beberapa domain selalu membutuhkan minimal satu elemen. Dengan NonEmptyList, validasi dilakukan sejak awal, bukan di downstream.

## 5. Fit dengan prinsip Exact

Semua struktur ini menurut prinsip:

- explicit over magic
- immutable by default
- correctness over convenience
- native PHP friendly

## 6. Roadmap lanjutan

- `LazyList` atau lazy evaluation
- `Map` dan `Set` immutable yang lebih kuat
- property-based testing untuk koleksi
- docs benchmark untuk performa
