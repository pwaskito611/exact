# PHP Compatibility

## Minimum Runtime

`composer.json` declares PHP `>=8.2`. PHPUnit is a development dependency at `^11.0`; it is not a runtime dependency of the library.

## Language Features

The source uses PHP 8.2-compatible features including readonly classes, constructor property promotion, union types, callable type declarations, and `never` return types. It also relies on standard PHP exception and generator behavior.

## Support Evidence

There is no upper PHP bound, CI matrix, or compatibility test matrix in the repository. Therefore PHP 8.2 is the declared minimum, while behavior across later PHP versions is not separately verified here.

## Policy Status

The repository does not define a formal deprecation policy, release support window, or semantic-versioning policy. Changes should preserve the declared API and tests where compatibility is intended, but no stronger policy should be inferred from this document.