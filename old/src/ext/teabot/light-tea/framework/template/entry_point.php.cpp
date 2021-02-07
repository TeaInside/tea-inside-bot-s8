<?php $ltp = LightTeaPHP::getIns(); ?>

#include <php.h>
#include "entry_point.h"

ZEND_DECLARE_MODULE_GLOBALS(<?php echo $ltp->extName; ?>);

ZEND_GET_MODULE(<?php echo $ltp->extName; ?>);

#include "class_ce_extern.frag.h"

static PHP_MINIT_FUNCTION(<?php echo $ltp->extName; ?>)
{
  {
    zend_class_entry ce;
    #include "class.frag.cpp"
  }

  REGISTER_INI_ENTRIES();
  return SUCCESS;
}

static PHP_MSHUTDOWN_FUNCTION(<?php echo $ltp->extName; ?>)
{
  UNREGISTER_INI_ENTRIES();
  return SUCCESS;
}

static PHP_GINIT_FUNCTION(<?php echo $ltp->extName; ?>)
{
#if defined(COMPILE_DL_ASTKIT) && defined(ZTS)
  ZEND_TSRMLS_CACHE_UPDATE();
#endif
}

zend_module_entry <?php echo $ltp->extName; ?>_module_entry = {
  STANDARD_MODULE_HEADER,
  "<?php echo $ltp->extName; ?>",
  NULL, /* functions */
  PHP_MINIT(<?php echo $ltp->extName; ?>),
  PHP_MSHUTDOWN(<?php echo $ltp->extName; ?>),
  NULL, /* RINIT */
  NULL, /* RSHUTDOWN */
  NULL, /* MINFO */
  "<?php echo $ltp->version; ?>",
  PHP_MODULE_GLOBALS(<?php echo $ltp->extName; ?>),
  PHP_GINIT(<?php echo $ltp->extName; ?>),
  NULL, /* GSHUTDOWN */
  NULL, /* RPOSTSHUTDOWN */
  STANDARD_MODULE_PROPERTIES_EX
};
