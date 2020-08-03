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
   * @var mixed
   */
  private $retVal;

  /**
   * @var array
   */
  private array $vars;

  /**
   * @var string
   */
  private string $name = "~";

  /**
   * @var callable
   */
  private $beforeCallback;

  /**
   * @param \PDO     $pdo
   * @param callable $callback
   * @param array    $vars
   */
  public function __construct(PDO $pdo, callable $callback, array $vars = [])
  {
    $this->pdo = $pdo;
    $this->vars = $vars;
    $this->callback = $callback;
  }

  /**
   * @param callable $callback
   * @return void
   */
  public function setErrorCallback(callable $callback): void
  {
    $this->errorCallback = $callback;
  }

  /**
   * @param callable $callback
   * @return void
   */
  public function setBeforeCallback(callable $callback): void
  {
    $this->beforeCallback = $callback;
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
   * Useful for debugging.
   *
   * @param string $name
   */
  public function setName(string $name): void
  {
    $this->name = $name;
  }

  /**
   * @return mixed
   */
  public function getRetVal()
  {
    return $this->retVal;
  }

  /**
   * @return bool
   */
  public function execute(): bool
  {
    /*debug:7*/
    $cid = \Swoole\Coroutine::getCid();
    $trxName = $this->name;
    /*enddebug*/

    $pdo = $this->pdo;
    $tryCounter = 0;

    if (is_callable($this->beforeCallback)) {
      call_user_func_array($this->beforeCallback, [&$this->vars]);
    }

    tryLabel:

    try {
      $tryCounter++;

      $pdo->beginTransaction();

      /*debug:7*/
      var_dump("[{$cid}] beginTransaction [{$trxName}]");
      /*enddebug*/

      $this->retVal = call_user_func_array(
        $this->callback,
        [$pdo, &$this->vars]
      );

      $pdo->commit();

      /*debug:7*/
      var_dump("[{$cid}] commit [{$trxName}]");
      /*enddebug*/

      return true;

    } catch (PDOException $e) {

      $pdo->rollback();
      /*debug:7*/
      var_dump("[{$cid}] rollback [{$trxName}]");
      /*enddebug*/

      if (preg_match("/Deadlock found/S", $e->getMessage())) {

        /*debug:7*/
        var_dump("[$cid] ".$e->getMessage());
        /*enddebug*/

        if ($tryCounter <= $this->deadlockTryCount) {

          /*debug:7*/
          var_dump(
            "[$cid][tryCounter:{$tryCounter}][sleep:{$this->trySleep}] Recovering from deadlock [{$trxName}]..."
          );
          /*enddebug*/

          sleep($this->trySleep);

          goto tryLabel;
        }
      }

      echo "[".date("c")."]\n".$e;

      if (is_callable($this->errorCallback)) {
        $this->retVal = call_user_func_array(
          $this->errorCallback,
          [$pdo, $e, &$this->vars]
        );
      } else {
        $this->retVal = false;
      }
    } /*debug:7*/ finally {
      DB::dropTransactionState();
    } /*enddebug*/

    return false;
  }
}
