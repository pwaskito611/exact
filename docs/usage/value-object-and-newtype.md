# Value Object dan NewType

## 1. Tujuan

Value object dan newtype membantu membatasi domain agar tidak bergantung pada primitive mentah seperti `string`, `int`, atau `array` yang rentan salah pakai.

## 2. Value Object

```php
<?php

use Exact\ValueObject;
use InvalidArgumentException;

final class CustomerName extends ValueObject
{
    public static function from(string $name): self
    {
        return new self(['name' => $name]);
    }

    protected function validate(): void
    {
        if (trim((string) ($this->data['name'] ?? '')) === '') {
            throw new InvalidArgumentException('Name cannot be empty');
        }
    }
}
```

### Keuntungan

- validasi dilakukan saat object dibuat
- object menjadi konsisten dari awal
- mudah dibandingkan dengan object lain

## 3. NewType

```php
<?php

use Exact\NewType;
use InvalidArgumentException;

final class EmailAddress extends NewType
{
    protected static function validate(mixed $value): void
    {
        if (!is_string($value) || !str_contains($value, '@')) {
            throw new InvalidArgumentException('Email address must contain @');
        }
    }
}

$email = EmailAddress::from('user@example.com');
```

### Kapan dipakai

- ID domain seperti `CustomerId`, `OrderId`
- value semantik seperti `EmailAddress`, `Amount`, `CurrencyCode`
- tipe yang harus valid sebelum diproses lebih lanjut

## 4. Praktik bagus

- jangan gunakan string mentah jika ada semantic domain yang lebih kuat
- validasi paling awal di constructor atau factory
- buat type yang jelas dan eksplisit
