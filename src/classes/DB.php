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

      /*debug:5*/
      var_dump("opening PDO cid: ".$cid.", active PDO cid: ".count($dbCollections));
      /*enddebug*/
    } else {
      /*debug:5*/
      var_dump("retake PDO cid: ".$cid);
      /*enddebug*/
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

    /*debug*/
    var_dump("closing PDO cid: ".$cid.", active PDO cid: ".count($dbCollections));
    /*enddebug*/
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
