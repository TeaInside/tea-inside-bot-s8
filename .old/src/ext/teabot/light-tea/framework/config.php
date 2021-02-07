<?php

define("BASE_FRAMEWORK_DIR", __DIR__);
define("TEMPLATE_DIR", BASE_FRAMEWORK_DIR."/template");


$strconsts = [
  "ZEND_ACC_PUBLIC",
  "ZEND_ACC_PRIVATE",
  "ZEND_ACC_PROTECTED",
  "ZEND_ACC_STATIC",
  "ZEND_ACC_FINAL",
  "ZEND_ACC_ABSTRACT",
  "ZEND_ACC_CTOR",
  "ZEND_ACC_DTOR",
];

foreach ($strconsts as $k => $v) {
  define($v, $v);
}
