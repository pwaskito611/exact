<?php

declare(strict_types=1);

namespace Exact\Collection;

use Generator;

final class LazySeq
{
    /**
     * @param callable(): iterable<mixed> $source
     */
    private function __construct(
        private readonly mixed $source,
    ) {
    }

    /**
     * @param iterable<mixed> $source
     */
    public static function from(iterable $source): self
    {
        return new self(
            static function () use ($source): iterable {
                yield from $source;
            }
        );
    }

    /**
     * @param callable(): iterable<mixed> $source
     */
    public static function defer(callable $source): self
    {
        return new self($source);
    }

    public function map(callable $fn): self
    {
        return self::defer(function () use ($fn): Generator {
            foreach ($this->iterate() as $item) {
                yield $fn($item);
            }
        });
    }

    public function filter(callable $predicate): self
    {
        return self::defer(function () use ($predicate): Generator {
            foreach ($this->iterate() as $item) {
                if ($predicate($item)) {
                    yield $item;
                }
            }
        });
    }

    public function take(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException(
                'Count cannot be negative.'
            );
        }

        return self::defer(function () use ($count): Generator {
            if ($count === 0) {
                return;
            }

            $taken = 0;

            foreach ($this->iterate() as $item) {
                yield $item;

                $taken++;

                if ($taken >= $count) {
                    break;
                }
            }
        });
    }

    public function toList(): Seq
    {
        return Seq::fromArray(
            iterator_to_array($this->iterate(), false)
        );
    }

    /**
     * @return Generator<int, mixed>
     */
    private function iterate(): Generator
    {
        $source = ($this->source)();

        yield from $source;
    }
}