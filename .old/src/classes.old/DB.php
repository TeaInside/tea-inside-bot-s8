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
   * @param array    $vars
   */
  public static function transaction(callable $callback, array $vars = [])
  {
    /*debug:7*/
    global $transactionStates;
    $cid = Swoole\Coroutine::getCid();
    if (!is_array($transactionStates)) {
      $transactionStates = [$cid => true];
    } else {
      $transactionStates[$cid] = true;
    }
    /*enddebug*/
    return new DBTransaction(DB::pdo(), $callback, $vars);
  }

  /**
   * @return void
   */
  public static function dropTransactionState()
  {
    /*debug:7*/
    global $transactionStates;
    $cid = Swoole\Coroutine::getCid();
    unset($transactionStates[$cid]);
    /*enddebug*/
  }

  /**
   * @param string $name
   * @return bool
   */
  public static function mustBeInTransaction(string $name = "~")
  {
    /*debug:7*/
    global $transactionStates;
    $cid = Swoole\Coroutine::getCid();
    if (isset($transactionStates[$cid]) && $transactionStates[$cid]) {
      return true;
    } else {
      throw new Exception("Error logic: {$name} is not in transaction state.");
    }
    /*enddebug*/
    return false;
  }

  /**
   * @param string $name
   * @return bool
   */
  public static function mustNotBeInTransaction(string $name = "~")
  {
    /*debug:7*/
    global $transactionStates;
    $cid = Swoole\Coroutine::getCid();
    if (isset($transactionStates[$cid]) && $transactionStates[$cid]) {
      throw new Exception("Error logic: {$name} is not in transaction state.");
    } else {
      return true;
    }
    /*enddebug*/
    return false;
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
