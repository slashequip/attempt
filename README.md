# Attempt

Attempt is a simple, fluent class for attempting to run code multiple times whilst handling exceptions. It attempts
to mimic PHPs built-in try/catch syntax where possible but sprinkles in some additional magic on top.

## Getting an instance

Depending on your preference you can grab an Attempt instance in a couple of ways:

```php
use SlashEquip\Attempt\Attempt;

$attempt = new Attempt();

$attempt = Attempt::make();
```


## Building your Attempt

Once you have your instance you can begin to build your Attempt.

### Try

This is the only required method, the `try` method accepts a callable argument, the code that you want to run.

```php
$attempt->try(function () {
    // My code that may or may not work.
})->thenReturn();
```

### Then Return

You may have noticed in the example above the method `thenReturn`, this method is what tells the Attempt to run.
It will also return the value you return from the callable you pass to the `try` method.

There is also a `then` method, this too accepts a callable which is executed and passed the value returned
by your `try` callable.

If it's your kind of jam, an attempt is also invokable which means at any point you can invoke the Attempt and it will run.

```php
// $valueOne will be true
$valueOne = $attempt->try(function () {
    return true;
})->thenReturn();

// $valueTwo will be false
$valueTwo = $attempt->try(function () {
    return true;
})->then(function ($result) {
    return !$result;
});

// $valueThree will be true
$valueThree = $attempt->try(function () {
    return true;
})();
```

### Times

You can set the amount of times the Attempt should be made whilst an exception is being encountered.

### Catch

The `catch` method allows you to define exceptions you are expecting to encounter during the attempts, when 
exceptions have been passed to the catch method the Attempt will throw any other types of exceptions it
comes across _early_ rather than performing all attempts.

The `catch` method can be called multiple times to add multiple expected exceptions.

_If you do no provide any expected exception via the `catch` method then the Attempt will ignore all exceptions
until all attempts have been made._

```php
$attempt
    ->try(function () {
        throw new UnexpectedException;
    })
    ->catch(TheExceptionWeAreExpecting::class)
    ->catch(AnotherExceptionWeAreExpecting::class)
    ->thenReturn();

// In this example; only one attempt would be made and a UnexpectedException would be thrown
```

### Finally

The `finally` method allows you to run a callback at the end of the attempt _no matter the result_, whether the attempt
was successful or an exception was thrown the `finally` callback will be run.

```php
$attempt
    ->try(function () {
        throw new UnexpectedException;
    })
    ->finally(function () {
        // run some clean up.
    })
    ->thenReturn();

// In this example; only one attempt would be made and a UnexpectedException would be thrown
```

### Wait Between

The `waitBetween` method takes an integer indicating the desired number of milliseconds to wait between attempts.

## Example use case

```php
use SlashEquip\Attempt\Attempt;
use GuzzleHttp\Exception\ClientException;

$blogPost = Attempt::make()
    ->try(function () use ($data) {
        return \App\UnstableBlogApiServiceUsingGuzzle::post([
           'data' => $data, 
        ]);
    })
    ->times(3)
    ->waitBetween(250)
    ->catch(ClientException::class)
    ->then(function ($apiResponse) {
        return \App\BlogPost::fromApiResponse($apiResponse);
    });
```