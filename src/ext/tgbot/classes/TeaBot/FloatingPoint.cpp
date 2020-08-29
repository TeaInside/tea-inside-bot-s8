
#include "../../teabot8.h"
#include <stdio.h>
#include <math.h>
#include <string.h>
#include <stdbool.h>

typedef union {
  unsigned int x;
  float f;
  struct {
    unsigned int mantisa : 23;
    unsigned int exponent : 8;
    unsigned int sign : 1;
  } p;
} float_cast;

#define ZUP(A, B, C) \
  zend_update_property_ ## A (class_ce, this_ce, ZEND_STRL((B)), ((C)) TSRMLS_DC)

extern "C" {

zend_class_entry *ce_TeaBot__FloatingPoint;

#define class_ce (ce_TeaBot__FloatingPoint)

bool rand_init;

inline static float float_rand( float min, float max )
{
  float scale = rand() / (float) RAND_MAX; /* [0, 1.0] */
  return min + scale * ( max - min );      /* [min, max] */
}

/**
 * @param array $config
 *
 * Constructor.
 */
PHP_METHOD(TeaBot__FloatingPoint, __construct)
{
  zval *this_ce;
  float_cast x;
  char buffer0[32];
  char buffer1[16];

  if (!rand_init) {
    srand((unsigned int)time(NULL));
    rand_init = true;
  }

  x.f = float_rand(-1000, 1000);
  sprintf(buffer0, "%f", x.f);
  sprintf(buffer1, "%#x", x.x);

  this_ce = getThis();

  ZUP(string, "str",       buffer0);
  ZUP(string, "str_hex",   buffer1);
  ZUP(long,   "mantisa",   x.p.mantisa);
  ZUP(long,   "exponent",  x.p.exponent);
  ZUP(long,   "sign",      x.p.sign);
}

zend_function_entry methods_TeaBot__FloatingPoint[] = {
  PHP_ME(TeaBot__FloatingPoint, __construct, NULL, ZEND_ACC_CTOR | ZEND_ACC_PUBLIC)
  PHP_FE_END
};

}
