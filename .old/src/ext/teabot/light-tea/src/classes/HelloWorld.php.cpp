
#include <string>

<?php
LightTeaPHP::beginFile(__FILE__);
$hello = new PHPClass("HelloWorld", __FILE__);
$hello->start();
?>

static <?php $hello->method("__construct", [ZEND_ACC_PUBLIC, ZEND_ACC_CTOR]); ?> {
  php_printf("HelloWorld constructor is called\n");
}

static <?php $hello->method("print", [ZEND_ACC_PUBLIC]); ?> {
  php_printf("Hello World!\n");
}

<?php
$hello->end();
LightTeaPHP::addClass($hello);
LightTeaPHP::endFile(__FILE__);
