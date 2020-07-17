<?php

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package {No Package}
 * @version 8.0
 */

$dbCollections = [];

final class DB
{
  /**
   * @return \PDO
   */
  public static function pdo(): \PDO
  {
    global $dbCollections;
    $cid = Swoole\Coroutine::getCid();

    if (!isset($dbCollections[$cid])) {
      $dbCollections[$cid] = new \PDO(...PDO_PARAM);
    }

    return $dbCollections[$cid];
  }

  /**
   * @return void
   */
  public static function close(): void
  {
    global $dbCollections;
    unset($dbCollections[Swoole\Coroutine::getCid()]);
  }
}
