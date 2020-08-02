<?php

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package {No Package}
 * @version 8.0
 */

final class DB
{
  /**
   * @return \PDO
   */
  public static function pdo(): \PDO
  {
    global $dbCollections;
    $cid = Swoole\Coroutine::getCid();

    if ((!is_array($dbCollections)) || (!isset($dbCollections))) {
      $dbCollections = [];
    }

    if (!isset($dbCollections[$cid])) {
      $dbCollections[$cid] = new \PDO(...PDO_PARAM);

      /*__debug_flag:5:41IAg7LEoviU0twCDaX8gtS8zLx0hQAXf4XkzBQrBSU9FSCtp6SjkJhcklmWiiyTnF+aV6KhkpLknJ+TkwqUzs8r1tS05oIYCgA=*/
    }
    /*__debug_flag:5:41IAgtSc4lSFai4FMChLLIpPKc0t0FAqSi1JzE5VCHDxV0jOTLFSUNJTAdKa1mCFtWASAA==*/

    return $dbCollections[$cid];
  }

  /**
   * @return void
   */
  public static function close(): void
  {
    global $dbCollections;
    $cid = Swoole\Coroutine::getCid();

    unset($dbCollections[$cid]);

    /*__debug_flag:5:41IAgsw0BQ0Nxczi+MSiosRKDZWUJOf8nJzU5JLM/LxiTU2FmhoFkHRxagmGnKZCNZcCGKDKKNgqRMdag6VqwWRZYlF8SmlugYZSck5+cWZeukKAi79CcmaKlYKSngqQ1lPSUUgEai5LRZZJzi/Nw7QVYjAA*/
  }

  /**
   * @return void
   */
  public static function dumpConnections()
  {
    global $dbCollections;
    var_dump($dbCollections);
  }
}
