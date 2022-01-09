<?php

namespace SlashEquip\Attempt;

use SlashEquip\Attempt\Exceptions\NoTryCallbackSetException;
use Throwable;
use Closure;

class Attempt
{
    protected int $attempts = 0;

    protected int $times = 1;

    protected int $waitBetween = 0;

    protected ?Closure $try = null;

    protected ?Closure $finally = null;

    protected array $expects = [];

    public function __invoke(): mixed
    {
        $this->validate();

        while ($this->attempts < $this->times)
        {
            // Increase the attempt number.
            ++$this->attempts;

            // Wait if needed.
            $this->runWait();

            try {
                $result = ($this->try)();
            } catch (Throwable $e) {
                // We have reached max number of attempts.
                if ($this->attempts === $this->times) {
                    $this->handleException($e);
                }

                // Not expecting specific exceptions so continue with loop.
                if (empty($this->expects)) {
                    continue;
                }

                // This exception is something we expect to see.
                if (in_array(get_class($e), $this->expects)) {
                    continue;
                }

                // Nothing left to do but throw.
                $this->handleException($e);
            }

            return $this->handleSuccess($result);
        }
    }

    public static function make(): static
    {
        return new static();
    }

    public function catch(string $exceptionClass): static
    {
        $this->expects[] = $exceptionClass;

        return $this;
    }

    public function times(int $times): static
    {
        $this->times = $times;

        return $this;
    }

    public function try(callable $callback): static
    {
        $this->try = $callback;

        return $this;
    }

    public function finally(callable $callback): static
    {
        $this->finally = $callback;

        return $this;
    }

    public function waitBetween(int $milliseconds): static
    {
        $this->waitBetween = $milliseconds;

        return $this;
    }

    public function then(callable $callback): mixed
    {
        return $callback($this->thenReturn());
    }

    public function thenReturn(): mixed
    {
        return $this();
    }

    protected function validate(): void
    {
        if (!$this->try) {
            throw new NoTryCallbackSetException();
        }
    }

    protected function handleSuccess($value): mixed
    {
        $this->runFinally();
        return $value;
    }

    protected function handleException(Throwable $e): void
    {
        $this->runFinally();
        throw $e;
    }

    protected function runFinally(): void
    {
        if ($this->finally) {
            ($this->finally)();
        }
    }

    protected function runWait(): void
    {
        if ($this->waitBetween && $this->attempts > 1) {
            usleep($this->waitBetween * 1000);
        }
    }
}
