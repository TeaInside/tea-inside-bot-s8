
#ifndef USER_HPP
#define USER_HPP

#include <string>
#include <cstdlib>

class User {
private:
  char    *_name = NULL;
  size_t  _name_len = 0;
public:
  User(char *name, size_t name_len);
  ~User();
  char   *getName();
  size_t getNameLen();
};

#endif
