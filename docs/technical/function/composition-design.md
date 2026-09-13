# Design: Function Composition

## Function Representation

Exact represents functions as PHP `callable` values. `Composition::compose()` and `Composition::pipe()` create unary callables. `Functions` creates identity, constant, curried, and partially applied callables. `Pipe` stores one value and applies callables through `map()` or `through()`.

## Evaluation Order

- `compose(f, g)` evaluates `g` first and then `f`.
- `pipe(f, g)` evaluates `f` first and then `g`.
- `Pipe::through(f, g)` evaluates in supplied order.
- `Functions::curry()` collects one value at a time until its declared arity is reached.

With no functions, `compose()` and `pipe()` return an identity-like callable for the supplied value.

## Type Considerations

PHP checks callable invocation and declared parameter types at runtime. The project does not enforce a generic relationship between the output of one callable and the input of the next. `curry()` also does not independently verify that declared arity matches the callable signature.

## Error Propagation

These utilities do not catch callback exceptions or convert callback errors to `Result`. A callback exception propagates according to normal PHP behavior. To model expected failure as a value, compose callbacks that return `Option`, `Result`, or another Exact abstraction.

## Related Documents

- [Composition](../composition.md)
- [Type Model](../type-model.md)
- [Usage: Functions](../../usage/functional/functions.md)