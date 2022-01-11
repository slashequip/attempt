<?php

namespace SlashEquip\Attempt;

class AttemptConfiguration
{
    protected int $times = 1;

    protected int $waitBetween = 0;

    protected bool $shouldThrow = true;

    public static function clone(AttemptConfiguration $configuration)
    {
        $clone = new AttemptConfiguration;
        $clone->setTimes($configuration->getTimes());
        $clone->setWaitBetween($configuration->getWaitBetween());
        $clone->setShouldThrow($configuration->getShouldThrow());

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
}
