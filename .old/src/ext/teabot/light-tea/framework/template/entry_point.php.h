<?php $ltp = LightTeaPHP::getIns(); ?>

#ifndef __<?php echo $ltp->upExtName; ?>_H
#define __<?php echo $ltp->upExtName; ?>_H

#ifdef HAVE_CONFIG_H
  #include "config.h"
#endif

#include <php.h>

extern zend_module_entry <?php echo $ltp->extName; ?>_module_entry;

PHP_INI_BEGIN()
PHP_INI_END()

ZEND_BEGIN_MODULE_GLOBALS(<?php echo $ltp->extName; ?>)
ZEND_END_MODULE_GLOBALS(<?php echo $ltp->extName; ?>)

ZEND_EXTERN_MODULE_GLOBALS(<?php echo $ltp->extName; ?>)

#define <?php echo $ltp->upExtName; ?>G(v) ZEND_MODULE_GLOBALS_ACCCESSOR(<?php echo $ltp->extName; ?>, v)

#define phpext_<?php echo $ltp->extName; ?>_ptr (&<?php echo $ltp->extName; ?>_module_entry)

#endif
