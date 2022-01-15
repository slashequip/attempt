<?php

namespace SlashEquip\Attempt;

use SlashEquip\Attempt\Exceptions\NoTryCallbackSetException;
use Throwable;
use Closure;

class Attempt
{
    private int $attempts = 0;

    public function __construct(
        protected ?Closure $try = null,
        protected ?Closure $finally = null,
        protected ?AttemptConfiguration $configuration = null
    ) {
        if (! $configuration) {
            $this->configuration = new AttemptConfiguration;
        }
    }

    public function __invoke(): mixed
    {
        $this->validate();

        while ($this->attempts < $this->configuration->getTimes()) {
            // Increase the attempt number.
            ++$this->attempts;

            // Wait if needed.
            $this->runWait();

            try {
                $result = ($this->try)();
            } catch (Throwable $e) {
                // We have reached max number of attempts.
                if ($this->attempts === $this->configuration->getTimes()) {
                    return $this->handleException($e);
                }

                // Not expecting specific exceptions so continue with loop.
                if (empty($this->expects)) {
                    continue;
                }

                // This exception is something we expect to see.
                if ($this->expectsException($e)) {
                    continue;
                }

                // Nothing left to do but throw.
                return $this->handleException($e);
            }

            return $this->handleSuccess($result);
        }
    }

    public static function make(): static
    {
        return new static();
    }

    public function catch(string $exceptionClass, ?callable $callable = null): static
    {
        $configuration = AttemptConfiguration::clone($this->configuration);
        $configuration->appendExpectation($exceptionClass, $callable);

        return new static(
            $this->try,
            $this->finally,
            $configuration,
        );
    }

    public function times(int $times): static
    {
        $configuration = AttemptConfiguration::clone($this->configuration);
        $configuration->setTimes($times);

        return new static(
            $this->try,
            $this->finally,
            $configuration,
        );
    }

    public function try(callable $callback): static
    {
        return new static(
            $callback,
            $this->finally,
            AttemptConfiguration::clone($this->configuration),
        );
    }

    public function finally(callable $callback): static
    {
        return new static(
            $this->try,
            $callback,
            AttemptConfiguration::clone($this->configuration),
        );
    }

    public function waitBetween(int $milliseconds): static
    {
        $configuration = AttemptConfiguration::clone($this->configuration);
        $configuration->setWaitBetween($milliseconds);

        return new static(
            $this->try,
            $this->finally,
            $configuration,
        );
    }

    public function noThrow(): static
    {
        $configuration = AttemptConfiguration::clone($this->configuration);
        $configuration->setShouldThrow(false);

        return new static(
            $this->try,
            $this->finally,
            $configuration,
        );
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

        if (! $this->configuration->getShouldThrow()) {
            return;
        }

        if ($handler = $this->getExceptionHandler($e)) {
            $handler ? $handler($e) : null;
            return;
        }

        throw $e;
    }

    protected function expectsException(Throwable $e)
    {
        return isset($this->configuration->getExpectations()[get_class($e)]);
    }

    protected function getExceptionHandler(Throwable $e): ?callable
    {
        return $this->expectsException($e)
            ? $this->configuration->getExpectations()[get_class($e)]
            : null;
    }

    protected function runFinally(): void
    {
        if ($this->finally) {
            ($this->finally)();
        }
    }

    protected function runWait(): void
    {
        if ($this->configuration->getWaitBetween() && $this->attempts > 1) {
            usleep($this->configuration->getWaitBetween() * 1000);
        }
    }
}
