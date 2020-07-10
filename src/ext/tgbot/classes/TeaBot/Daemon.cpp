
#include "../../teabot8.h"

extern "C" {

zend_class_entry *teabot8_daemon_ce;

/**
 * @param array $config
 *
 * Constructor.
 */
PHP_METHOD(TeaBot__Daemon, __construct)
{
  ZEND_PARSE_PARAMETERS_START(1, 1)
  ZEND_PARSE_PARAMETERS_END();
}




zend_function_entry teabot8_methods[] = {
  PHP_ME(TeaBot__Daemon, __construct, NULL, ZEND_ACC_CTOR | ZEND_ACC_PUBLIC)
  PHP_FE_END
};

}

