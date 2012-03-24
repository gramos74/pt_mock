<?php

/*
 * (c) Gabriel Ramos <gabi@gabiramos.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * pt_mock
 *
 * @author     Gabriel Ramos <gabi@gabiramos.com>
 * 
 * Original idea of Mathias Biilmann [http://mathias-biilmann.net/]
 *
 */

// ---------------------------------------------------------------
// pt_mock_common
// ---------------------------------------------------------------
class pt_mock_common {

  protected $_logger;
  protected $_pre_error_message;


  public function __construct($logger = null) {
    $this->_logger = $logger;
    $this->_prefix_error = "";
  }


  public function _log($level, $message) {
    if (!is_null($this->_logger)) {
      foreach(explode("\n",trim($message)) as $line) $this->_logger->$level("{$this->_prefix_error} $line");
    }
  }


  protected function _sort_args($args) {
    ksort($args);
    foreach($args as $key => $value) {
      if(is_array($value)) $args[$key] = $this->_sort_args($value);
      if (is_object($value)) $args[$key] = "Object: (".spl_object_hash($value).")";
    }
    return $args;
  }


  protected function _get_hash($args) {
    return md5(print_r($args,true));
  }

}




// ---------------------------------------------------------------
// pt_mock
// ---------------------------------------------------------------
class pt_mock extends pt_mock_common {

  private static $_mocks = array();
  private $_name;
  private $_expects;
  private $_stubs;
  private $_errors = array();


  public function __construct($name, $logger = null) {
    parent::__construct($logger);

    $this->_name = $name;
    $this->_expects = array();
    $this->_stubs = array();
    $this->_prefix_error = "pt_mock [{$name}]: ";
    self::$_mocks[] = $this;
  }


  public function expects($method_name) {
    $this->_log('debug',"Defined ({$method_name}) as expectation");
    $expect = new pt_mock_expectation($this->_name, $method_name, $this->_logger);
    $this->_expects[$method_name][] = $expect;
    return $expect;
  }


  public function stubs($method_name) {
    $this->_log('debug',"Defined ({$method_name}) as stub");
    $stub = new pt_mock_stub($this->_name, $method_name, $this->_logger);
    $this->_stubs[$method_name][] = $stub;
    return $stub;
  }


  public static function reset_all() {
    foreach(self::$_mocks as $mock) $mock->reset();
    self::$_mocks = array();
  }


  public function reset() {
    $this->_expects = array();
    $this->_stubs = array();
    $this->_errors = array();
    }


  public static function verify_all() {
    $errors = array();
    foreach(self::$_mocks as $mock) {
      try {
        $mock->verify();
      } catch (pt_mock_exception $e) {
        $errors[] = $e->getMessage();
      }
    }
    self::reset_all();

    if (count($errors) > 0) {
      throw new pt_mock_exception(implode("\n", $errors));
    } else {
      return true;
    }
  }


  public function verify() {
    foreach ($this->_expects as $method_name => $expectations) {
      foreach($expectations as $expect) {
        try {
          $expect->verify();
        } catch (pt_mock_exception $e) {
          $this->_errors[] = $e->getMessage();
        }
      }
    }

    if (count($this->_errors) > 0) {
      $this->_log('info',"Does not verify");
      throw new pt_mock_exception(implode("\n", $this->_errors));
    } else {
      $this->_log('info',"Verify");
      return true;
    }
  }


  public function __call($name , $args) {

    $args = $this->_sort_args($args);
    $hash = count($args) ? $this->_get_hash($args) : '_null_';

    $this->_log("debug","Received call for method ({$name}) with args ($hash):\n".print_r($args,true));

    $options = array();

    if (isset($this->_stubs[$name])) {
      foreach (array_reverse($this->_stubs[$name]) as $stub) {
        $stub_hash = is_null($stub->_get_args_hash()) ? '_null_' : $stub->_get_args_hash();
        $options[$stub_hash] = $stub;
      }
    }

    if (isset($this->_expects[$name])) {
      foreach (array_reverse($this->_expects[$name]) as $expect) {
        $expect_hash = is_null($expect->_get_args_hash()) ? '_null_' : $expect->_get_args_hash();
        if (!$expect->is_matched()) $options[$expect_hash] = $expect;
      }
    }

    try {

      if (isset($options[$hash])) return $options[$hash]->_get_result_of_call();
      if (isset($options['_null_'])) return $options['_null_']->_get_result_of_call();

      if (count($options) == 0) {
        $message = "[{$this->_name}]\n\nCannot find any stub or expecation for call [{$name}] with arguments: \n".print_r($args,true);
        $this->_errors[] = "[{$this->_name}]: {$message}";
        throw new pt_mock_exception($message);

      } else if (count($options) == 1) {
        $option = array_shift($options);
        $message = "[{$this->_name}]\n\nExpected parameters for [{$name}]: \n".print_r($option->_get_args(),true)."\n But received :".print_r($args,true);
        $this->_errors[] = "[{$this->_name}]: {$message}";
        throw new pt_mock_exception($message);

      } else {
        $message  = "[{$this->_name}]\n\nCannot match any stub or expecation for call [{$name}] with arguments: \n".print_r($args,true)."\n";
        $message .= "Similar expectations are :\n";
        foreach($options as $option) {
          $message .= get_class($option)." with args:\n".print_r($option->_get_args(),true)."\n";
        }

        $this->_errors[] = "[{$this->_name}]: {$message}";
        throw new pt_mock_exception($message);
      }

    } catch (pt_mock_exception $e) {
      $this->_log('err', $e->getMessage());
      throw $e;
    }
  }

}




// ---------------------------------------------------------------
// pt_mock_stub
// ---------------------------------------------------------------
class pt_mock_stub extends pt_mock_common {

  protected $_mock_name;
  protected $_method_name;
  protected $_args;
  protected $_hash;
  protected $_value_to_return;
  protected $_exception_to_raise;


  public function __construct($mock_name, $method_name, $logger = null) {
    parent::__construct($logger);
    $this->_mock_name = $mock_name;
    $this->_method_name = $method_name;
    $this->_args = null;
    $this->_hash = null;
    $this->_value_to_return = null;
    $this->_exception_to_raise = null;
    $this->_prefix_error = "Stub [$method_name]: ";
  }


  public function with() {
    $args = func_get_args();
    $this->_args = $this->_sort_args($args);
    $this->_hash = $this->_get_hash($this->_args);
    $this->_log('debug',"Defined args for ({$this->_hash}):\n".print_r($this->_args,true));
    return $this;
  }


  public function returns($value) {
    $this->_value_to_return = $value;
    return $this;
  }


  public function raises($exception) {
    $this->_exception_to_raise = $exception;
    return $this;
  }


  public function _get_result_of_call() {
    if (!is_null($this->_value_to_return)) {
      $response = is_object($this->_value_to_return) ? "Object:".spl_object_hash($this->_value_to_return) : print_r($this->_value_to_return,true);
      $this->_log("debug","Response is: ".$response);
      return $this->_value_to_return;
    } else if (!is_null($this->_exception_to_raise)) {
      $this->_log("debug","Raises exception [".get_class($this->_exception_to_raise)."]:".$this->_exception_to_raise->getMessage());
      throw $this->_exception_to_raise;
    } else {
      $this->_log("debug","Response is: null");
      return null;
    }
  }


  public function _get_args() {
    return $this->_args;
  }


  public function _get_args_hash() {
    return $this->_hash;
  }
}




// ---------------------------------------------------------------
// pt_mock_expectation
// ---------------------------------------------------------------
class pt_mock_expectation extends pt_mock_stub {

  private $_times;
  private $_expected_times;


  public function __construct($mock_name, $method_name, $logger = null) {
    parent::__construct($mock_name, $method_name, $logger);
    $this->_prefix_error = "Expectation [$method_name]: ";
    $this->_times = 1;
    $this->_expected_times = 1;
  }


  public function times($times) {
    if ($times > 0 ) {
      $this->_times = $times;
      $this->_expected_times = $times;
    }
    return $this;
  }


  public function never() {
    $this->_times = null;
    $this->_expected_times = null;
    return $this;
  }


  public function is_matched() {
    if ($this->_times === 0) return true;
    return false;
  }


  public function _get_result_of_call() {
    if (is_null($this->_times)) {
      $message = "[{$this->_mock_name}]\n\nMethod ({$this->_method_name}) called but is expected to not be called";
      $this->_log("err", $message);
      $this->_errors[] = $message;
      throw new pt_mock_exception($message);

    } else if ($this->_times === 0) {
      $message = "[{$this->_mock_name}]\n\nMethod ({$this->_method_name}) expected to be called {$this->_times} times but called at least one more";
      $this->_log("err", $message);
      $this->_errors[] = $message;
      throw new pt_mock_exception($message);

    } else {
      $this->_times -= 1;
      return parent::_get_result_of_call();
    }
  }


  public function verify() {
    if ($this->_times >= 1) {
      $times_called = $this->_expected_times - $this->_times;
      $message = "[{$this->_mock_name}]\n\nMethod ({$this->_method_name}) expected to be called {$this->_expected_times} times, but called {$times_called}";
      $this->_log("err", $message);
      throw new pt_mock_exception($message);
    }
    return true;
  }

}




// ---------------------------------------------------------------
// pt_mock_exception
// ---------------------------------------------------------------
class pt_mock_exception extends Exception {}
