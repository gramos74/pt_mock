<?php

namespace Pt;

class Expectation extends Stub
{

    private $times;
    private $expectedTimes;


    public function __construct($mockName, $methodName, $logger = null)
    {
        parent::__construct($mockName, $methodName, $logger);
        $this->prefixError = "Expectation [$methodName]: ";
        $this->times = 1;
        $this->expectedTimes = 1;
    }


    public function times($times)
    {
        if ($times > 0) {
            $this->times = $times;
            $this->expectedTimes = $times;
        }
        return $this;
    }


    public function never()
    {
        $this->times = null;
        $this->expectedTimes = null;
        return $this;
    }


    public function isMatched()
    {
        if ($this->times === 0) {
            return true;
        }
        return false;
    }


    public function getResultOfCall()
    {
        if (is_null($this->times)) {
            $message = "[{$this->mockName}]\n\nMethod ({$this->methodName}) called but is expected to not be called";
            $this->log("err", $message);
            $this->errors[] = $message;
            throw new MockException($message);
        } elseif ($this->times === 0) {
            $message = "[{$this->mockName}]\n\nMethod ({$this->methodName}) expected to be called {$this->times} times but called at least one more";
            $this->log("err", $message);
            $this->errors[] = $message;
            throw new MockException($message);
        } else {
            $this->times -= 1;
            return parent::getResultOfCall();
        }
    }


    public function verify()
    {
        if ($this->times >= 1) {
            $timesCalled = $this->expectedTimes - $this->times;
            $message = "[{$this->mockName}]\n\nMethod ({$this->methodName}) expected to be called {$this->expectedTimes} times, but called {$timesCalled}";
            $this->log("err", $message);
            throw new MockException($message);
        }
        return true;
    }
}
