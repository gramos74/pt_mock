<?php

namespace Pt;

class MockTest extends \PHPUnit_Framework_TestCase
{


    public function tearDown()
    {
        Mock::resetAll();
    }



    public function test_calling_mock_without_expectations_and_stubs()
    {
        $error  = "[Test]\n\nCannot find any stub or expecation for call [any_non_declared_method] with arguments:
Array
(
)
";
        $mock = new Mock('Test');
        $this->setExpectedException('\Pt\MockException', $error);
        $mock->any_non_declared_method();
    }

    public function test_calling_mock_with_stub_but_missing_arguments()
    {
        $error = "
@@ @@
 Array (
     0 => 'param1'
-    1 => 'param2'
 )
";

        $mock = new Mock('Test');
        $mock->stubs('method')->with("param1", "param2")->returns("hola");

        $this->setExpectedException('\Pt\MockException', $error);
        $mock->method("param1");
    }

    public function test_calling_mock_with_stub_but_bad_complex_arguments()
    {
        $error = "
@@ @@
 Array (
     0 => true
     1 => Array (
-        'index' => 'value'
+        'index' => 'value_other'
         'index2' => Array (
             0 => 2
-            1 => 3
-            2 => 6
+            1 => 'str'
         )
         'index3' => true
     )
     2 => 5
 )
";

        $mock = new Mock('Test');
        $mock->stubs('method')->with(
                true,
                array('index' => 'value',
                      'index2' => array(2, 3, 6),
                      'index3' => true),
                5)
            ->returns("hola");

        $this->setExpectedException('\Pt\MockException', $error);
        $mock->method(
            true,
            array('index' => 'value_other',
                  'index2' => array(2, 'str'),
                  'index3' => true),
            5
            );
    }

    public function test_calling_mock_with_two_stubs_but_bad_arguments()
    {
        $error = "[Test]

Cannot match any stub or expecation for call [method] with arguments:
Array
(
    [0] => param1
)

Similar expectations are :
Pt\Stub with args:

--- Expected
+++ Actual
@@ @@
 Array (
     0 => 'param1'
-    1 => 'param2'
-    2 => 'param3'
 )

Pt\Stub with args:

--- Expected
+++ Actual
@@ @@
 Array (
     0 => 'param1'
-    1 => 'param2'
 )
";

        $mock = new Mock('Test');
        $mock->stubs('method')->with("param1", "param2")->returns("hola");
        $mock->stubs('method')->with("param1", "param2", "param3")->returns("returned data");

        $this->setExpectedException('\Pt\MockException', $error);
        $mock->method("param1");
    }



    public function test_calling_mock_with_default_stub()
    {
        $mock = new Mock('Test');
        $mock->stubs('method')->returns("hola");
        $mock->stubs('method')->with("param1", "param2", "param3")->returns("returned data");

        $this->assertEquals("hola", $mock->method("param1"));
        $this->assertTrue(Mock::verifyAll());
    }



    public function test_calling_mock_with_stubbed_method_and_one_expectation_not_verified()
    {
        $mock = new Mock('Test');
        $mock->stubs('method')->returns("hola");
        $mock->expects('method')->with("param1", "param2", "param3")->returns("returned data");

        $this->setExpectedException('\Pt\MockException', "[Test]\n\nMethod (method) expected to be called 1 times, but called 0");
        $this->assertEquals("hola", $mock->method("param1"));
        $this->assertFalse(Mock::verifyAll());
    }



    public function test_calling_mock_with_stubbed_method_and_one_expectation_verified()
    {
        $mock = new Mock('Test');
        $mock->stubs('method')->returns("hola");
        $mock->expects('method')->with("param1")->returns("returned data");

        $this->assertEquals("returned data", $mock->method("param1"));
        $this->assertTrue($mock->verify());
    }



    public function test_calling_mock_with_one_expectation_less_times_than_expected()
    {
        $mock = new Mock('Test');
        $mock->expects('method')->with("param1")->times(2)->returns("returned data");

        $this->assertEquals("returned data", $mock->method("param1"));
        $this->setExpectedException('\Pt\MockException', "[Test]\n\nMethod (method) expected to be called 2 times, but called 1");
        $mock->verify();
    }



    public function test_calling_mock_with_one_expectation_that_should_not_be_called_never()
    {
        $mock = new Mock('Test');
        $mock->expects('method')->with("param1")->never()->returns("returned data");

        $this->setExpectedException('\Pt\MockException', "[Test]\n\nMethod (method) called but is expected to not be called");
        $mock->method("param1");
    }



    public function test_calling_method_with_no_parameters()
    {
        $mock = new Mock('Test');
        $mock->expects('method')->returns("returned data");

        $this->assertEquals("returned data", $mock->method());
        $this->assertTrue(Mock::verifyAll());
    }
}
