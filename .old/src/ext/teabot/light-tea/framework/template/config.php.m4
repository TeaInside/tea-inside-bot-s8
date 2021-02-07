
<?php $ltp = LightTeaPHP::getIns(); ?>

dnl config.m4

PHP_ARG_ENABLE(<?php echo $ltp->extName; ?>, for <?php echo $ltp->extName; ?> support, [  --enable-<?php echo $ltp->extName; ?>            Include <?php echo $ltp->extName; ?> support])

PHP_REQUIRE_CXX()

PHP_NEW_EXTENSION(<?php echo $ltp->extName; ?>, <?php $ltp->printFiles(); ?>, $ext_shared)
<?php $ltp->printIncludePath(); ?>
PHP_SUBST(<?php echo $ltp->upExtName; ?>_SHARED_LIBADD)
