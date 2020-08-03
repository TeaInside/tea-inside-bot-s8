<?php

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package {No Package}
 * @version 8.0
 */
final class DBTransaction
{
  /**
   * @var \PDO
   */
  private PDO $pdo;

  /**
   * @var callable
   */
  private $callback;

  /**
   * @var callable
   */
  private $errorCallback;

  /**
   * @var int
   */
  private int $deadlockTryCount = 10;

  /**
   * @var int
   */
  private int $trySleep = 2;

  /**
   * @param \PDO     $pdo
   * @param callable $callback
   */
  public function __construct(PDO $pdo, callable $callback)
  {
    $this->pdo = $pdo;
    $this->callback = $callback;
  }

  /**
   * @param callable $callback
   */
  public function setErrorCallback(callable $callback)
  {
    $this->errorCallback = $callback;
  }

  /**
   * @param int
   * @return void
   */
  public function setDeadlockTryCount(int $num): void
  {
    $this->deadlockTryCount = $num;
  }

  /**
   * @param int
   * @return void
   */
  public function setTrySleep(int $num): void
  {
    $this->trySleep = $num;
  }

  /**
   * @return bool
   */
  public function execute(): bool
  {
    /*debug:7*/
    $cid = \Swoole\Coroutine::getCid();
    /*enddebug*/

    $pdo = $this->pdo;
    $tryCounter = 0;

    tryLabel:

    try {
      $tryCounter++;

      $pdo->beginTransaction();

      /*debug:7*/
      var_dump("[{$cid}] beginTransaction");
      /*enddebug*/

      call_user_func_array($this->callback, [$pdo]);

      $pdo->commit();

      /*debug:7*/
      var_dump("[{$cid}] commit");
      /*enddebug*/

    } catch (PDOException $e) {

      $pdo->rollback();
      /*debug:7*/
      var_dump("[{$cid}] rollback");
      /*enddebug*/

      if (preg_match("/Deadlock found/S", $e->getMessage())) {

        /*debug:7*/
        var_dump("[$cid] ".$e->getMessage());
        /*enddebug*/

        if ($tryCounter <= $this->deadlockTryCount) {

          /*debug:7*/
          var_dump("[$cid] Recovering from deadlock...");
          /*enddebug*/

          goto tryLabel;
        }
      }

      echo "[".date("c")."]\n".$e;

      if (is_callable($this->errorCallback)) {
        call_user_func_array($this->errorCallback, [$pdo, $e]);
      }
    }
  }
}
