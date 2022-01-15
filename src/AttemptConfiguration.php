<?php

namespace SlashEquip\Attempt;

class AttemptConfiguration
{
    protected int $times = 1;

    protected int $waitBetween = 0;

    protected bool $shouldThrow = true;

    protected array $expectations = [];

    public static function clone(AttemptConfiguration $configuration)
    {
        $clone = new AttemptConfiguration;
        $clone->setTimes($configuration->getTimes());
        $clone->setWaitBetween($configuration->getWaitBetween());
        $clone->setShouldThrow($configuration->getShouldThrow());
        $clone->setExpectations($configuration->getExpectations());

        return $clone;
    }

    public function setTimes(int $times): void
    {
        $this->times = $times;
    }

    public function getTimes(): int
    {
        return $this->times;
    }

    public function setWaitBetween(int $seconds): void
    {
        $this->waitBetween = $seconds;
    }

    public function getWaitBetween(): int
    {
        return $this->waitBetween;
    }

    public function setShouldThrow(bool $shouldThrow): void
    {
        $this->shouldThrow = $shouldThrow;
    }

    public function getShouldThrow(): bool
    {
        return $this->shouldThrow;
    }

    public function setExpectations(array $expectations): void
    {
        $this->expectations = $expectations;
    }

    public function appendExpectation(string $exception, ?callable $callable = null): void
    {
        $this->expectations[$exception] = $callable;
    }

    public function getExpectations(): array
    {
        return $this->expectations;
    }
}
