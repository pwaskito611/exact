# Mutation Testing

Mutation testing is not currently configured. The repository has no Infection dependency, `infection.json5`, mutation command, or mutation score.

## Intended Use

If introduced, mutation testing would complement PHPUnit by checking whether tests detect controlled changes to branch dispatch, collection invariants, exception paths, and transformation behavior.

## Interpretation

A mutation score would be evidence about the test suite's sensitivity to selected mutations, not a complete measure of API quality, performance, or correctness. Until tooling is added, no mutation-quality claim can be made for Exact.