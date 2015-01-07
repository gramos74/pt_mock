[![Build Status](https://travis-ci.org/trilopin/pt_mock.png?branch=master)](https://travis-ci.org/trilopin/pt_mock)
[![Build status](https://ci.appveyor.com/api/projects/status/pp3dthsbli5ysk7y)](https://ci.appveyor.com/project/trilopin/pt-mock)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/trilopin/pt_mock/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/trilopin/pt_mock/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/trilopin/pt_mock/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/trilopin/pt_mock/?branch=master)


pt_mock
--------------------------------------------------------------------------------

This class works with PHP >= 5.3.6.
In order to run the tests, phpunit is required.

This class provides a way to mock objects when testing. Makes it simple to
simulate method calls returning data based on parameters. Can raise exceptions
instead of return data.

Source in [https://github.com/gramos74/pt_mock]




Examples
--------------------------------------------------------------------------------

We have a method class that receives a object as parameter. Inside this method
we will call the object method 'method_mock' with parameter 'a'
and we expect that it returns 'b'.

```php
    class class_a {

        function my_method(class_b) {
            return class_b->method_mock('a');
        }

    }

    $class_b = new \Pt\Mock('Class B');
    $class_b->expects('method_mock')->with('a')->returns('b');

    $class_a = new class_a();
    echo $class_a->my_method($class_b);  // ----> 'b'
```

To check that all expectations have been accomplished :

    $class_b->verify();     // for a instance of pt_mock
    \Pt\Mock::verifyAll();  // for all mocks instantiated


If you want to test that the method is called two times:

```php
    $class_b->expects('method_mock')->with('a')->times(2)->returns('b');
```

Sometimes you don't want to test if a method is called, you only want that if a
method is called the mock object returns a value based on parameters.

```php
    $class_b->stubs('method_mock')->with('a')->returns('b');

    echo $class_b->method_mock('a') ---> 'b'
    echo $class_b->method_mock('a') ---> 'b'
    echo $class_b->method_mock('a') ---> 'b'
    echo $class_b->method_mock('a') ---> 'b'
```


And sometimes you want to raise a exception instead of to return data.

    $class_b->stubs('method_mock')->with('a')->raises(new Exception());

    echo $class_b->method_mock('a') ---> raises a exception




Credits
--------------------------------------------------------------------------------

Thanks a lot to Mathias Biilmann [http://mathias-biilmann.net/] by the original
idea. Was a awesome experience work together !




License
--------------------------------------------------------------------------------

Copyright (c) 2011 Released under the MIT license (see MIT-LICENSE)
Gabriel Ramos <gabi@gabiramos.com>