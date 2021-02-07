
#include <string>
#include <cstring>
#include <cstdint>
#include <cstdbool>

#include <poll.h>
#include <signal.h>
#include <arpa/inet.h>
#include <sys/socket.h>
#include <netinet/tcp.h>

<?php
LightTeaPHP::beginFile(__FILE__);
$dmn = new PHPClass("TeaBot\\Telegram\\Daemon", __FILE__);
$dmn->start();
?>

#define TCP_BACKLOG   (100)
#define POLL_ARR_SIZE (TCP_BACKLOG + 2)
#define MAX_TCP_BIND_STR (32)

/**
 * @param array $resp
 * @param array $logg
 *
 * Constructor.
 */
static <?php $dmn->method("__construct", [ZEND_ACC_PUBLIC, ZEND_ACC_CTOR]); ?> {
  register zval *addr;
  register zval *resp;
  register zval *logg;
  register zval *_this;

  ZEND_PARSE_PARAMETERS_START(3, 3)
    Z_PARAM_ZVAL(addr)
    Z_PARAM_ARRAY(resp)
    Z_PARAM_ARRAY(logg)
  ZEND_PARSE_PARAMETERS_END_EX(RETURN_FALSE);

  _this = getThis();
  zend_update_property(<?= $dmn->ce ?>, _this, ZEND_STRL("addr"), addr TSRMLS_CC);
  zend_update_property(<?= $dmn->ce ?>, _this, ZEND_STRL("resp"), resp TSRMLS_CC);
  zend_update_property(<?= $dmn->ce ?>, _this, ZEND_STRL("logg"), logg TSRMLS_CC);
}


/**
 * @param zval *logg
 * @param char (*logg_workers)[MAX_TCP_BIND_STR]
 * @return bool
 */
inline static bool logg_assign(
  register zval *logg,
  register char (*logg_workers)[MAX_TCP_BIND_STR]
) {
  register zval *val;
  register bool ok = true;
  register unsigned int ii = 0;

  ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(logg), val) {
    register char *vv;
    register size_t ll;

    if (Z_TYPE_P(val) != IS_STRING) {
      ok = false;
      zend_error(E_WARNING, "responder entry must be a string");
      break;
    }

    vv = Z_STRVAL_P(val);
    ll = Z_STRLEN_P(val);

    if (ll >= MAX_TCP_BIND_STR) {
      ok = false;
      zend_error(E_WARNING,
        "logger \"%s\" is too long, max length is %d",
        vv, MAX_TCP_BIND_STR - 1);
      break;
    }

    memcpy(logg_workers[ii], vv, ll);
    logg_workers[ii][ll] = '\0';
    ii++;

  } ZEND_HASH_FOREACH_END();

  return ok;
}


/**
 * @param zval *resp
 * @param char (*resp_workers)[MAX_TCP_BIND_STR]
 * @return bool
 */
inline static bool resp_assign(
  register zval *resp,
  register char (*resp_workers)[MAX_TCP_BIND_STR]
) {

  register zval *val;
  register bool ok = true;
  register unsigned int ii = 0;

  ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(resp), val) {
    register char *vv;
    register size_t ll;

    if (Z_TYPE_P(val) != IS_STRING) {
      ok = false;
      zend_error(E_WARNING, "responder entry must be a string");
      break;
    }

    vv = Z_STRVAL_P(val);
    ll = Z_STRLEN_P(val);

    if (ll >= MAX_TCP_BIND_STR) {
      ok = false;
      zend_error(E_WARNING,
        "responder \"%s\" is too long, max length is %d",
        vv, MAX_TCP_BIND_STR - 1);
      break;
    }

    memcpy(resp_workers[ii], vv, ll);
    resp_workers[ii][ll] = '\0';
    ii++;

  } ZEND_HASH_FOREACH_END();

  return ok;
}

#define SET_SOCK_OPT(LEVEL, OPTNAME, OPTVAL, OPTLEN)              \
  if (setsockopt(sock_fd, LEVEL, OPTNAME, OPTVAL, OPTLEN) < 0) {  \
    perror("setsockopt()");                                       \
    zend_error(E_WARNING, "setsockopt() error");                  \
    return false;                                                 \
  }

inline static bool setup_socket(int sock_fd)
{
  int opt_1 = 1;

  SET_SOCK_OPT(SOL_SOCKET, SO_REUSEADDR, (void *)&opt_1, sizeof(opt_1));
  SET_SOCK_OPT(IPPROTO_TCP, TCP_NODELAY, (void *)&opt_1, sizeof(opt_1));

  return true;
}

#undef SET_SOCK_OPT

inline static bool parse_bind_scheme(
  register char *bind_scheme,
  register char **addr,
  register uint16_t *port
)
{
  *addr = bind_scheme;
  while (*bind_scheme++) {
    if (*bind_scheme == ':') {
      (*bind_scheme++) = '\0';
      break;
    }
  }
  *port = (uint16_t)atoi(bind_scheme);
  return true;
}

inline static int init_socket(
  register char *bind_scheme,
  struct sockaddr_in *srv_addr
)
{
  char      *addr;
  uint16_t  port;
  register int sock_fd;

  sock_fd = socket(AF_INET, SOCK_STREAM | SOCK_NONBLOCK, 0);
  if (sock_fd < 0) {
    perror("Socket creation failed");
    zend_error(E_WARNING, "Socket creation failed");
    return sock_fd;
  }

  if (!setup_socket(sock_fd)) {
    goto ret_err_close;
  }

  if (!parse_bind_scheme(bind_scheme, &addr, &port)) {
    goto ret_err_close;
  }

  bzero(srv_addr, sizeof(struct sockaddr_in));
  srv_addr->sin_family = AF_INET;
  srv_addr->sin_port = htons(port);
  srv_addr->sin_addr.s_addr = inet_addr(addr);

  return sock_fd;
ret_err_close:
  close(sock_fd);
  return -1;
}


inline static void handle_request(register struct pollfd *cl) {
  
}

inline static void accept_new_conn(
  register int sock_fd,
  register struct pollfd *fds,
  register nfds_t *nfds
) {
  int rv;
  struct sockaddr_in claddr;
  socklen_t rlen = sizeof(struct sockaddr_in);

  rv = accept(sock_fd, (struct sockaddr *)&claddr, &rlen);
  if ((rv < 0) && (errno != EWOULDBLOCK)) {
    perror("accept()");
    zend_error(E_WARNING, "accept error");
    return;
  }

  fds[*nfds].fd = rv;
  fds[*nfds].fd = POLLIN;
  *nfds++;
}

inline static void spawn_master_worker(
  register char *bind_scheme,
  register zval *_this,
  register char (*resp_workers)[MAX_TCP_BIND_STR],
  register int resp_n,
  register char (*logg_workers)[MAX_TCP_BIND_STR],
  register int logg_n
)
{
  nfds_t nfds;
  bool stop = false;
  int sock_fd, rv, timeout;
  struct sockaddr_in srv_addr;
  struct pollfd fds[POLL_ARR_SIZE];

  sock_fd = init_socket(bind_scheme, &srv_addr);
  if (sock_fd < 0) return;


  rv = bind(sock_fd, (struct sockaddr *)&srv_addr, sizeof(struct sockaddr_in));
  if (rv < 0) {
    perror("Bind failed");
    zend_error(E_WARNING, "Bind socket failed");
    return;
  }


  if (listen(sock_fd, 100) < 0) {
    perror("Listen failed");
    zend_error(E_WARNING, "Listen socket failed");
    return;
  }

  /* Ignore SIGPIPE */
  signal(SIGPIPE, SIG_IGN);

  printf("Listening on %s...\n", bind_scheme);
  fflush(stdout);

  fds[0].fd = sock_fd;
  fds[0].events = POLLIN;

  nfds = 1;
  timeout = 3000;

  while (1) {
    register int rv;

    rv = poll(fds, nfds, timeout);

    /* Poll reached its timeout. */
    if (rv == 0) {
      goto end_loop;
    }

    if (fds[0].revents == POLLIN) {
      accept_new_conn(sock_fd, fds, &nfds);
    }

    for (register nfds_t i = 1; i < nfds; i++) {
      if (fds[i].revents == POLLIN) {
        handle_request(&(fds[i]));
      }      
    }

    end_loop:
    if (stop) return;
  }
}


/**
 * @return bool
 */
static <?php $dmn->method("run", [ZEND_ACC_PUBLIC]); ?> {

  register bool err = false;
  register int resp_n, logg_n;
  register char (*resp_workers)[MAX_TCP_BIND_STR] = NULL;
  register char (*logg_workers)[MAX_TCP_BIND_STR] = NULL;
  register zval *addr;
  register zval *resp;
  register zval *logg;
  register zval *_this;
  register zval *val;
  zval rv;

  _this = getThis();
  addr = zend_read_property(<?= $dmn->ce ?>, _this, ZEND_STRL("addr"), 1, &rv TSRMLS_CC);
  resp = zend_read_property(<?= $dmn->ce ?>, _this, ZEND_STRL("resp"), 1, &rv TSRMLS_CC);
  logg = zend_read_property(<?= $dmn->ce ?>, _this, ZEND_STRL("logg"), 1, &rv TSRMLS_CC);
  resp_n = zend_hash_num_elements(Z_ARRVAL_P(resp));
  logg_n = zend_hash_num_elements(Z_ARRVAL_P(logg));


  if (!resp_n) {
    zend_error(E_WARNING, "responder workers cannot be empty");
    goto ret;
  }
  if (!logg_n) {
    zend_error(E_WARNING, "logger workers cannot be empty");
    goto ret;
  }

  resp_workers = (char (*)[MAX_TCP_BIND_STR])malloc(MAX_TCP_BIND_STR * resp_n);
  logg_workers = (char (*)[MAX_TCP_BIND_STR])malloc(MAX_TCP_BIND_STR * logg_n);

  if (!resp_assign(resp, resp_workers)) goto ret_free;
  if (!logg_assign(logg, logg_workers)) goto ret_free;

  spawn_master_worker(Z_STRVAL_P(addr), _this, resp_workers, resp_n, logg_workers, logg_n);
ret_free:
  free(resp_workers);
  free(logg_workers);
ret:
  RETURN_FALSE;
}

<?php
$dmn->end();
LightTeaPHP::addClass($dmn);
LightTeaPHP::endFile(__FILE__);
