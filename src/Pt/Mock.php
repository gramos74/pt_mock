<?php

namespace Pt;

class Mock extends Common
{

    private static $mocks = array();
    private $mockName;
    private $expects;
    private $stubs;
    private $errors = array();


    public function __construct($name, $logger = null)
    {
        parent::__construct($logger);

        $this->mockName = $name;
        $this->expects = array();
        $this->stubs = array();
        $this->prefixError = "mock [{$name}]: ";
        self::$mocks[] = $this;
    }


    public function expects($methodName)
    {
        $this->log('debug', "Defined ({$methodName}) as expectation");
        $expect = new Expectation($this->mockName, $methodName, $this->logger);
        $this->expects[$methodName][] = $expect;
        return $expect;
    }


    public function stubs($methodName)
    {
        $this->log('debug', "Defined ({$methodName}) as stub");
        $stub = new Stub($this->mockName, $methodName, $this->logger);
        $this->stubs[$methodName][] = $stub;
        return $stub;
    }


    public static function resetAll()
    {
        foreach (self::$mocks as $mock) {
            $mock->reset();
        }
        self::$mocks = array();
    }


    public function reset()
    {
        $this->expects = array();
        $this->stubs = array();
        $this->errors = array();
    }


    public static function verifyAll()
    {
        $errors = array();
        foreach (self::$mocks as $mock) {
            try {
                $mock->verify();
            } catch (MockException $e) {
                $errors[] = $e->getMessage();
            }
        }
        self::resetAll();

        if (count($errors) > 0) {
            throw new MockException(implode("\n", $errors));
        } else {
            return true;
        }
    }


    public function verify()
    {
        foreach ($this->expects as $methodName => $expectations) {
            foreach ($expectations as $expect) {
                try {
                    $expect->verify();
                } catch (MockException $e) {
                    $this->errors[] = $e->getMessage();
                }
            }
        }

        if (count($this->errors) > 0) {
            $this->log('info', "Does not verify");
            throw new MockException(implode("\n", $this->errors));
        } else {
            $this->log('info', "Verify");
            return true;
        }
    }


    public function __call($name, $args)
    {
        $args = $this->sortArgs($args);
        $hash = count($args) ? $this->getHash($args) : '_null_';

        $this->log("debug", "Received call for method ({$name}) with args ($hash):\n".print_r($args, true));

        $options = array();

        if (isset($this->stubs[$name])) {
            foreach (array_reverse($this->stubs[$name]) as $stub) {
                $stub_hash = is_null($stub->getArgsHash()) ? '_null_' : $stub->getArgsHash();
                $options[$stub_hash] = $stub;
            }
        }

        if (isset($this->expects[$name])) {
            foreach (array_reverse($this->expects[$name]) as $expect) {
                $expect_hash = is_null($expect->getArgsHash()) ? '_null_' : $expect->getArgsHash();
                if (!$expect->isMatched()) {
                    $options[$expect_hash] = $expect;
                }
            }
        }

        try {
            if (isset($options[$hash])) {
                return $options[$hash]->getResultOfCall();
            }
            if (isset($options['_null_'])) {
                return $options['_null_']->getResultOfCall();
            }


            if (count($options) === 0) {
                $message = "[{$this->mockName}]\n\nCannot find any stub or expecation for call [{$name}] with arguments:\n".print_r($args, true);
                $this->errors[] = "[{$this->mockName}]: {$message}";
                throw new MockException($message);
            } elseif (count($options) === 1) {
                $option = array_shift($options);

                $diff = $this->getDiff($option->getArgs(), $args);
                $message = "[{$this->name}]\n\nParameters mismatch for [{$name}]: {$diff}";
                $this->errors[] = "[{$this->name}]: {$message}";
                throw new MockException($message);
            } else {
                $message  = "[{$this->mockName}]\n\nCannot match any stub or expecation for call [{$name}] with arguments:\n".print_r($args, true)."\n";
                $message .= "Similar expectations are :\n";
                foreach ($options as $option) {
                    $diff = $this->getDiff($option->getArgs(), $args);
                    $message .= get_class($option);
                    if (!is_null($diff)) {
                        $message .= " with args:\n".$diff."\n";
                    }
                }

                $this->errors[] = "[{$this->mockName}]: {$message}";
                throw new MockException($message);
            }
        } catch (MockException $e) {
            $this->log('err', $e->getMessage());
            throw $e;
        }
    }

    private function getDiff($expected, $actual)
    {
        $factory = new \SebastianBergmann\Comparator\Factory();
        try {
            $comparator = $factory->getComparatorFor($expected, $actual);
            $comparator->assertEquals($expected, $actual);
        } catch (\SebastianBergmann\Comparator\ComparisonFailure $failure) {
            $diff = $failure->getDiff();
            return $diff;
        }
        return null;
    }
}
