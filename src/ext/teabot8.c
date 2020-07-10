
#include "teabot8.h"

ZEND_DECLARE_MODULE_GLOBALS(teabot8);

extern zend_class_entry teabot8_daemon_ce;
extern zend_function_entry teabot8_methods[];

static PHP_MINIT_FUNCTION(phpnasm)
{
  zend_class_entry ce;

  INIT_CLASS_ENTRY(ce, "TeaBot\\Daemon", teabot8_methods);
  teabot8_daemon_ce = zend_register_internal_class(&ce TSRMLS_CC);

  REGISTER_INI_ENTRIES();
  return SUCCESS;
}

static PHP_MSHUTDOWN_FUNCTION(phpnasm)
{
  UNREGISTER_INI_ENTRIES();
  return SUCCESS;
}


static PHP_GINIT_FUNCTION(teabot8)
{
#if defined(COMPILE_DL_ASTKIT) && defined(ZTS)
  ZEND_TSRMLS_CACHE_UPDATE();
#endif
}

zend_module_entry teabot8_module_entry = {
  STANDARD_MODULE_HEADER,
  "teabot8",
  NULL, /* functions */
  PHP_MINIT(teabot8),
  PHP_MSHUTDOWN(teabot8),
  NULL, /* RINIT */
  NULL, /* RSHUTDOWN */
  NULL, /* MINFO */
  "8.0",
  PHP_MODULE_GLOBALS(teabot8),
  PHP_GINIT(teabot8),
  NULL, /* GSHUTDOWN */
  NULL, /* RPOSTSHUTDOWN */
  STANDARD_MODULE_PROPERTIES_EX
};
