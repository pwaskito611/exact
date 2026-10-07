<?php

declare(strict_types=1);

namespace Exact\Pattern;

use Exact\Exception\MatchException;
use LogicException;

/**
 * Fluent pattern matcher.
 *
 * Accepts any object as subject. Keys may be:
 *  - class-strings (Some::class, None::class) matched via instanceof
 *  - semantic keys matched via Adt::matchKey() (Variant, custom Adt)
 *
 * Handlers receive the subject so type-hints narrow the type.
 *
 * @template TSubject of object
 */
final class Matcher
{
    /**
     * @var array<string, list<array{
     *     guard: (callable(TSubject): bool)|null,
     *     handler: callable(TSubject): mixed
     * }>>
     */
    private array $cases = [];

    /** @var (callable(TSubject): mixed)|null */
    private $defaultHandler = null;

    /** @var list<string>|null */
    private ?array $expectedKeys = null;

    private bool $executed = false;

    /**
     * @param TSubject $subject
     */
    public function __construct(
        private readonly object $subject,
    ) {
    }

    /**
     * Register a handler for one or more keys.
     *
     * @param string|list<string> $keys
     * @param callable(TSubject): mixed $handler
     */
    public function case(string|array $keys, callable $handler): self
    {
        return $this->caseWhen($keys, null, $handler);
    }

    /**
     * Register a guarded handler. Multiple caseWhen() calls for the same
     * key are evaluated in registration order; the first whose guard
     * returns true wins.
     *
     * @param string|list<string> $keys
     * @param (callable(TSubject): bool)|null $guard
     * @param callable(TSubject): mixed $handler
     */
    public function caseWhen(
        string|array $keys,
        ?callable $guard,
        callable $handler,
    ): self {
        $this->assertNotExecuted();

        foreach ((array) $keys as $key) {
            $this->cases[$key][] = [
                'guard' => $guard,
                'handler' => $handler,
            ];
        }

        return $this;
    }

    /**
     * Register a fallback handler used when no case matches.
     *
     * @param callable(TSubject): mixed $handler
     */
    public function default(callable $handler): self
    {
        $this->assertNotExecuted();
        $this->defaultHandler = $handler;

        return $this;
    }

    /**
     * Scala-style alias for default().
     *
     * @param callable(TSubject): mixed $handler
     */
    public function otherwise(callable $handler): self
    {
        return $this->default($handler);
    }

    /**
     * Declare the full set of keys this match must handle. Verified in
     * run() when no default() is registered.
     *
     * @param list<string> $keys
     */
    public function exhaustive(array $keys): self
    {
        $this->assertNotExecuted();
        $this->expectedKeys = $keys;

        return $this;
    }

    /**
     * Execute the match. Throws MatchException when nothing matches and
     * no default() is registered.
     */
    public function run(): mixed
    {
        $this->executed = true;

        $this->assertExhaustive();

        foreach ($this->cases as $key => $handlers) {
            if (!$this->matchesKey($key)) {
                continue;
            }

            foreach ($handlers as $case) {
                if ($case['guard'] === null || $case['guard']($this->subject)) {
                    return ($case['handler'])($this->subject);
                }
            }
        }

        if ($this->defaultHandler !== null) {
            return ($this->defaultHandler)($this->subject);
        }

        throw MatchException::noMatch($this->subject);
    }

    /**
     * Execute the match; return null when nothing matches.
     */
    public function runOrNull(): mixed
    {
        $this->executed = true;

        foreach ($this->cases as $key => $handlers) {
            if (!$this->matchesKey($key)) {
                continue;
            }

            foreach ($handlers as $case) {
                if ($case['guard'] === null || $case['guard']($this->subject)) {
                    return ($case['handler'])($this->subject);
                }
            }
        }

        if ($this->defaultHandler !== null) {
            return ($this->defaultHandler)($this->subject);
        }

        return null;
    }

    /**
     * Execute the match; return $default when nothing matches.
     */
    public function runOrElse(mixed $default): mixed
    {
        $this->executed = true;

        foreach ($this->cases as $key => $handlers) {
            if (!$this->matchesKey($key)) {
                continue;
            }

            foreach ($handlers as $case) {
                if ($case['guard'] === null || $case['guard']($this->subject)) {
                    return ($case['handler'])($this->subject);
                }
            }
        }

        if ($this->defaultHandler !== null) {
            return ($this->defaultHandler)($this->subject);
        }

        return $default;
    }

    /**
     * Execute the match; evaluate $default lazily when nothing matches.
     *
     * @param callable(): mixed $default
     */
    public function runOrElseGet(callable $default): mixed
    {
        $this->executed = true;

        foreach ($this->cases as $key => $handlers) {
            if (!$this->matchesKey($key)) {
                continue;
            }

            foreach ($handlers as $case) {
                if ($case['guard'] === null || $case['guard']($this->subject)) {
                    return ($case['handler'])($this->subject);
                }
            }
        }

        if ($this->defaultHandler !== null) {
            return ($this->defaultHandler)($this->subject);
        }

        return $default();
    }

    private function assertNotExecuted(): void
    {
        if ($this->executed) {
            throw new LogicException(
                'Cannot modify Matcher after it has been executed.'
            );
        }
    }

    private function assertExhaustive(): void
    {
        if ($this->expectedKeys === null || $this->defaultHandler !== null) {
            return;
        }

        $missing = array_diff($this->expectedKeys, array_keys($this->cases));

        if ($missing !== []) {
            throw new LogicException(
                'Non-exhaustive match. Missing cases: '
                . implode(', ', $missing)
            );
        }
    }

    private function matchesKey(string $key): bool
    {
        if ($this->subject instanceof Adt) {
            return $this->subject->matchKey() === $key;
        }

        if (class_exists($key) || interface_exists($key)) {
            return $this->subject instanceof $key;
        }

        return false;
    }
}