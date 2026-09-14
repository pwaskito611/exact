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