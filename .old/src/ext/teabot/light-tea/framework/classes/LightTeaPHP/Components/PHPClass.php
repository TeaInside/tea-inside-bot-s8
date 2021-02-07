<?php

namespace LightTeaPHP\Components;

use LightTeaPHP\LightTeaPHP;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \LightTeaPHP\Components
 */
class PHPClass
{
  /**
   * @var string
   */
  private $className;

  /**
   * @var string
   */
  private $hashName;

  /**
   * @var array
   */
  private $methods = [];

  /**
   * @var string
   */
  private $filename;

  /**
   * @var array
   */
  private $warnings;

  /**
   * @var string
   */
  private $error;

  /**
   * @var string
   */
  private $ce;

  /**
   * Constructor
   *
   * @param string $className
   * @param string $filename
   */
  public function __construct(string $className, string $filename = "")
  {
    $this->ltp        = LightTeaPHP::getIns();
    $this->className  = $className;
    $this->filename   = $filename;
    $this->hashName   = "ltp_".str_replace("\\", "_", $className);
    $this->ce = "ce_{$this->hashName}";
  }


  /**
   * @param mixed $key
   * @return mixed
   */
  public function __get($key)
  {
    return $this->{$key} ?? null;
  }


  /**
   * @return void
   */
  public function start(): void
  {
    echo "#ifdef __cplusplus\n";
    echo "extern \"C\" {\n";
    echo "#endif\n\n";
    echo "zend_class_entry *{$this->ce};\n";
  }


  /**
   * @param string $name
   * @param array  $attr
   * @return void
   */
  public function method(string $name, array $attr = [ZEND_ACC_PUBLIC]): void
  {
    echo "PHP_METHOD({$this->hashName}, {$name})";

    if ($name === "__construct") {
      if (!in_array(ZEND_ACC_CTOR, $attr)) {
        $this->warnings[] =
          "Warning: method named __construct, but there is no ZEND_ACC_CTOR attribute!\n";
      }
    }

    $this->methods[$name] = $attr;
  }

  /**
   * @return void
   */
  public function end(): void
  {
    echo "zend_function_entry methods_{$this->hashName}[] = {\n";

    foreach ($this->methods as $name => $attr) {
      $attr = implode(" | ", $attr);
      echo "  ";
      echo "PHP_ME({$this->hashName}, {$name}, NULL, {$attr})\n";
    }

    echo "  PHP_FE_END\n";
    echo "};\n";
    echo "\n";
    echo "#ifdef __cplusplus\n";
    echo "} /* extern \"C\" */\n";
    echo "#endif\n\n";

    $fixClassName = str_replace("\\", "\\\\", $this->className);
    $ltp = $this->ltp;

    file_put_contents(
      $ltp->classFragFile,
      "\nINIT_CLASS_ENTRY(ce, \"{$fixClassName}\", methods_{$this->hashName});\n"
      ."ce_{$this->hashName} = zend_register_internal_class(&ce TSRMLS_CC);\n",
      FILE_APPEND | LOCK_EX
    );

    file_put_contents(
      $ltp->classCeExternFile,
      "\nextern zend_class_entry *ce_{$this->hashName};\n"
      ."extern zend_function_entry methods_{$this->hashName}[];\n",
      FILE_APPEND | LOCK_EX
    );
  }
}
