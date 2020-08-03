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

      /*debug:5*/
      var_dump("opening PDO cid: ".$cid.", active PDO cid: ".count($dbCollections));
      /*enddebug*/
    }
    /*debug:5*/
    else {
      var_dump("retake PDO cid: ".$cid);
    }
    /*enddebug*/

    return $dbCollections[$cid];
  }

  /**
   * @param callable $callback
   */
  public static function transaction(callable $callback)
  {
    $pdo = DB::pdo();
    try {
      $pdo->beginTransaction();
      $callback($pdo);
      $pdo->commit();
    } catch (PDOException $e) {
      $pdo->rollBack();
    }
  }

  /**
   * @return void
   */
  public static function close(): void
  {
    global $dbCollections;
    $cid = Swoole\Coroutine::getCid();

    unset($dbCollections[$cid]);

    /*debug:5*/
    if ((!is_array($dbCollections)) || (!isset($dbCollections))) {
      $dbCollections = [];
    }
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
