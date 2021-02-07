<?php LightTeaPHP::beginFile(__FILE__); ?>

#define MODULE_NUM 177
#include "User.hpp"

User::~User() {
  if (this->_name != NULL) {
    efree(this->_name);
  }
}

User::User(char *name, size_t name_len) {
  this->_name = (char *)emalloc(name_len + 1);
  this->_name_len = name_len;

  memcpy(this->_name, name, name_len);
  this->_name[name_len] = '\0';
}

char *User::getName() {
  return this->_name;
}

size_t User::getNameLen() {
  return this->_name_len;
}

class UserP {
public:
  User *user;
  int res_num;
  UserP(char *name, size_t name_len, int res_num) {
    this->user = new User(name, name_len);
    this->res_num = res_num;
  }
  ~UserP() {
    delete this->user;
  }
};

<?php
$user = new PHPClass("User", __FILE__);
$user->start();
?>

inline static void user_delete(zend_resource *rsrc) {
  delete ((UserP *)rsrc->ptr);
}

/**
 * Constructor.
 *
 * @param string $name
 */
static <?= $user->method("__construct", [ZEND_ACC_CTOR, ZEND_ACC_PUBLIC]); ?> {
  UserP *userp;
  int res_num;
  size_t name_len;
  char *name = NULL;
  zval *_this, res_zv;
  zend_resource *user_res;

  ZEND_PARSE_PARAMETERS_START(1, 1)
    Z_PARAM_STRING(name, name_len)
  ZEND_PARSE_PARAMETERS_END_EX(RETURN_FALSE);

  if (name) {
    res_num = zend_register_list_destructors_ex(user_delete, NULL, "user", MODULE_NUM);

    userp = new UserP(name, name_len, res_num);
    user_res = zend_register_resource((void *)userp, res_num);
    ZVAL_RES(&res_zv, user_res);

    _this = getThis();
    zend_update_property(<?= $user->ce; ?>, _this, ZEND_STRL("obj"), &res_zv TSRMLS_CC);
  }
}

/**
 * @return string
 */
static <?= $user->method("getName", [ZEND_ACC_PUBLIC]); ?> {
  User *user;
  UserP *userp;
  int res_num;
  size_t name_len;
  char *name = NULL;
  zval *_this, *res_zv, rv;
  zend_resource *user_res;

  _this = getThis();
  res_zv = zend_read_property(<?= $user->ce; ?>, _this, ZEND_STRL("obj"), 1, &rv TSRMLS_CC);

  ZEND_ASSERT(Z_TYPE_P(res_zv) == IS_RESOURCE);

  user_res = res_zv->value.res;
  if (!user_res) {
    zend_error(E_WARNING, "Invalid user_res");
    RETURN_FALSE;
  }

  userp = (UserP *)user_res->ptr;
  if (!userp) {
    zend_error(E_WARNING, "Invalid userp");
    RETURN_FALSE;
  }

  userp = (UserP *)zend_fetch_resource(user_res, "user", userp->res_num);
  if (!userp) {
    zend_error(E_WARNING, "Invalid userp");
    RETURN_FALSE;
  }

  user  = userp->user;

  RETURN_STRINGL(user->getName(), user->getNameLen());
}

<?php
$user->end();
LightTeaPHP::addClass($user);
LightTeaPHP::endFile(__FILE__);
