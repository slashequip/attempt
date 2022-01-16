<?php

use SlashEquip\Attempt\Attempt;
use SlashEquip\Attempt\Exceptions\NoTryCallbackSetException;
use SlashEquip\Attempt\Tests\Exceptions\AttemptTestException;

it('throws exception when no try callback is given', function () {
    Attempt::make()->thenReturn();
})->throws(NoTryCallbackSetException::class);

it('makes the attempt the expected number of times', function () {
    $attempts = 0;
    $exceptionThrown = false;

    try {
        Attempt::make()
            ->try(function () use (&$attempts) {
                ++$attempts;
                throw new AttemptTestException();
            })
            ->times(3)
            ->thenReturn();
    } catch (AttemptTestException $e) {
        $exceptionThrown = true;
    }

    $this->assertTrue($exceptionThrown);
    $this->assertSame(3, $attempts);
});

it('will return early if the callback succeeds before the max attempts is reached', function () {
    $attempts = 0;

    Attempt::make()
        ->try(function () use (&$attempts) {
            ++$attempts;

            if ($attempts < 2) {
                throw new AttemptTestException();
            }
        })
        ->times(3)
        ->thenReturn();

    $this->assertSame(2, $attempts);
});

it('if expecting an exception it will throw if it encounters a different exception', function () {
    Attempt::make()
        ->try(function () {
            throw new BadMethodCallException();
        })
        ->times(3)
        ->catch(AttemptTestException::class)
        ->thenReturn();
})->throws(BadMethodCallException::class);

it('will not throw an exception when no throw is set', function () {
    $result = Attempt::make()
        ->try(function () {
            throw new BadMethodCallException();
        })
        ->noThrow()
        ->thenReturn();

    $this->assertNull($result);
});

it('will run a catch callback when expected exception is thrown', function () {
    $called = false;

    $result = Attempt::make()
        ->try(function () {
            throw new AttemptTestException();
        })
        ->catch(AttemptTestException::class, function () use (&$called) {
            $called = true;
        })
        ->thenReturn();

    $this->assertNull($result);
    $this->assertTrue($called);
});

it('will call final callback on success', function () {
    $finallyCalled = false;

    Attempt::make()
        ->try(function () {
            //
        })
        ->finally(function () use (&$finallyCalled) {
            $finallyCalled = true;
        })
        ->thenReturn();

    $this->assertTrue($finallyCalled);
});

it('will call final callback on exception', function () {
    $finallyCalled = false;

    try {
        Attempt::make()
            ->try(function () {
                throw new AttemptTestException();
            })
            ->finally(function () use (&$finallyCalled) {
                $finallyCalled = true;
            })
            ->thenReturn();
    } catch (AttemptTestException $e) {
        //
    }

    $this->assertTrue($finallyCalled);
});
