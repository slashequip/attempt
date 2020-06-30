<?php

namespace SlashEquip\Attempt;

use SlashEquip\Attempt\Exceptions\NoTryCallbackSetException;
use Throwable;

class Attempt
{
    /**
     * How many attempts have been made.
     * @var int
     */
    protected $attempts = 0;

    /**
     * How many times should we attempt.
     * @var int
     */
    protected $times = 1;

    /** @var int|null */
    protected $waitBetween;

    /**
     * The callback to attempt.
     *
     * @var callable
     */
    protected $try;

    /**
     * A callback that always gets run.
     *
     * @var callable|null
     */
    protected $finally;

    /**
     * The exceptions we are expecting to see.
     *
     * @var array
     */
    protected $expects = [];

    /**
     * @return mixed
     * @throws Throwable
     */
    public function __invoke()
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

    /**
     * @return static
     */
    public static function make(): self
    {
        return new static();
    }

    /**
     * @param string $exceptionClass
     *
     * @return $this
     */
    public function catch(string $exceptionClass): self
    {
        $this->expects[] = $exceptionClass;

        return $this;
    }

    /**
     * @param int $times
     *
     * @return $this
     */
    public function times(int $times): self
    {
        $this->times = $times;

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return mixed
     * @throws Throwable
     */
    public function try(callable $callback): self
    {
        $this->try = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function finally(callable $callback)
    {
        $this->finally = $callback;

        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return $this
     */
    public function waitBetween(int $milliseconds): self
    {
        $this->waitBetween = $milliseconds;

        return $this;
    }

    /**
     * @return mixed
     * @throws Throwable
     */
    public function then(callable $callback)
    {
        return $callback($this->thenReturn());
    }

    /**
     * @return mixed
     * @throws Throwable|NoTryCallbackSetException
     */
    public function thenReturn()
    {
        return $this();
    }

    /**
     * @return void
     * @throws NoTryCallbackSetException
     */
    protected function validate(): void
    {
        if (!$this->try) {
            throw new NoTryCallbackSetException();
        }
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    protected function handleSuccess($value)
    {
        $this->runFinally();
        return $value;
    }

    /**
     * @param Throwable $e
     *
     * @throws Throwable
     */
    protected function handleException(Throwable $e)
    {
        $this->runFinally();
        throw $e;
    }

    /**
     * @return void
     */
    protected function runFinally(): void
    {
        if ($this->finally) {
            ($this->finally)();
        }
    }

    /**
     * @return void
     */
    protected function runWait(): void
    {
        // If the user has defined a wait time and this isn't the first attempt.
        if ($this->waitBetween && $this->attempts > 1) {
            usleep($this->waitBetween * 1000);
        }
    }
}