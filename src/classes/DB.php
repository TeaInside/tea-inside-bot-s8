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
      $dbCollections[$cid]->exec("SET @@global.time_zone = '+00:00';");

      /*__debug_flag:41IAg7LEoviU0twCDaX8gtS8zLx0hQAXf4XkzBQrBSU9FSCtp6SjkJhcklmWiiyTnF+aV6KhkpLknJ+TkwqUzs8r1tS05oIYCgA=*/
    } else {
      /*__debug_flag:41IAg7LEoviU0twCDaWi1JLE7FSFABd/heTMFCsFJT0VIK1pzQVRCAA=*/
    }

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

    /*__debug_flag:41IAgrLEoviU0twCDaXknPzizLx0hQAXf4XkzBQrBSU9FSCtp6SjkJhcklmWiiyTnF+aV6KhkpLknJ+TkwqUzs8r1tS05gIZCQA=*/
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
