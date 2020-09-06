dnl config.m4
PHP_ARG_ENABLE(teabot8, for teabot8 support, [  --enable-teabot8            Include teabot8 support])

if test "$PHP_PHPNASM" != "no"; then
  PHP_REQUIRE_CXX()
  PHP_NEW_EXTENSION(teabot8, teabot8.c classes/TeaBot/FloatingPoint.cpp, $ext_shared)
  PHP_SUBST(TEABOT8_SHARED_LIBADD)
fi
