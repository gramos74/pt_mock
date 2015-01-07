<?php

namespace Pt;

class Stub extends Common
{

    protected $mockName;
    protected $methodName;
    protected $args;
    protected $hash;
    protected $valueToReturn;
    protected $exceptionToRaise;


    public function __construct($mockName, $methodName, $logger = null)
    {
        parent::__construct($logger);
        $this->mockName = $mockName;
        $this->methodName = $methodName;
        $this->args = null;
        $this->hash = null;
        $this->valueToReturn = null;
        $this->exceptionToRaise = null;
        $this->prefixError = "Stub [$methodName]: ";
    }


    public function with()
    {
        $args = func_get_args();
        $this->args = $this->sortArgs($args);
        $this->hash = $this->getHash($this->args);
        $this->log('debug', "Defined args for ({$this->hash}):\n".print_r($this->args, true));
        return $this;
    }


    public function returns($value)
    {
        $this->valueToReturn = $value;
        return $this;
    }


    public function raises($exception)
    {
        $this->exceptionToRaise = $exception;
        return $this;
    }


    public function getResultOfCall()
    {
        if (!is_null($this->valueToReturn)) {
            $response = is_object($this->valueToReturn) ? "Object:".spl_object_hash($this->valueToReturn) : print_r($this->valueToReturn, true);
            $this->log("debug", "Response is: ".$response);
            return $this->valueToReturn;
        } elseif (!is_null($this->exceptionToRaise)) {
            $this->log("debug", "Raises exception [".get_class($this->exceptionToRaise)."]:".$this->exceptionToRaise->getMessage());
            throw $this->exceptionToRaise;
        } else {
            $this->log("debug", "Response is: null");
            return null;
        }
    }


    public function getArgs()
    {
        return $this->args;
    }


    public function getArgsHash()
    {
        return $this->hash;
    }
}
