<?php

namespace Pt;

class Common
{

    protected $logger = null;
    protected $prefixError = null;


    public function __construct($logger = null)
    {
        $this->logger = $logger;
        $this->prefixError = "";
    }


    public function log($level, $message)
    {
        if (!is_null($this->logger)) {
            foreach (explode("\n", trim($message)) as $line) {
                $this->logger->$level("{$this->prefixError} $line");
            }
        }
    }


    protected function sortArgs($args)
    {
        ksort($args);
        foreach ($args as $key => $value) {
            if (is_array($value)) {
                $args[$key] = $this->sortArgs($value);
            }
            if (is_object($value)) {
                $args[$key] = "Object: (".spl_object_hash($value).")";
            }
        }
        return $args;
    }


    protected function getHash($args)
    {
        return md5(print_r($args, true));
    }
}
