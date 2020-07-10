
#ifndef __TEABOT8_H
#define __TEABOT8_H

#ifdef HAVE_CONFIG_H
  #include "config.h"
#endif

#include <php.h>

extern zend_module_entry teabot8_module_entry;

PHP_INI_BEGIN()
PHP_INI_END()

ZEND_BEGIN_MODULE_GLOBALS(teabot8)
ZEND_END_MODULE_GLOBALS(teabot8)

ZEND_EXTERN_MODULE_GLOBALS(teabot8)

#define TEABOT8G(v) ZEND_MODULE_GLOBALS_ACCCESSOR(teabot8, v)

#ifdef COMPILE_DL_TEABOT8
ZEND_GET_MODULE(teabot8)
#endif

#define phpext_teabot8_ptr (&teabot8_module_entry)

#endif
