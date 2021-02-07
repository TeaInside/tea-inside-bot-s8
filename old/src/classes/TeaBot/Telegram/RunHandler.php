<?php

namespace TeaBot\Telegram;

use Throwable;
use TeaBot\Telegram\Data;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
class RunHandler
{
  /**
   * @var callable
   */
  private $callback;

  /**
   * @var bool
   */
  private bool $status;

  /**
   * @var array
   */
  private array $params = [];

  /**
   * @var mixed
   */
  private $e;

  /**
   * @var mixed
   */
  private $ret;

  /**
   * @var 
   */
  private ?Data $data;

  /**
   * @param ?\TeaBot\Telegram\Data $data
   *
   * Constructor.
   */
  public function __construct(?Data $data = null)
  {
    $this->data = $data;
  }

  /**
   * @param callable $callback
   * @return void
   */
  public function setCallback(callable $callback): void
  {
    $this->callback = $callback;
  }

  /**
   * @param array $params
   * @return voic
   */
  public function setParams(array $params)
  {
    $this->params = $params;
  }

  /**
   * @return bool
   */
  public function run(): bool
  {
    try {

      $this->ret = call_user_func_array($this->callback, $this->params);
      return true;

    } catch (Throwable $e) {

      $this->e = $e;
      self::report($e);
      return false;

    }
  }

  /**
   * @return mixed
   */
  public function getRetVal()
  {
    return $this->ret;
  }

  /**
   * @param mixed $e
   * @return void
   */
  public static function report($e): void
  {
    if (defined("TELEGRAM_ERROR_REPORT_CHAT_ID")
        && is_array(TELEGRAM_ERROR_REPORT_CHAT_ID)) {


        $now        = date("Y-m-d H:i:s");
        $reportData = <<<STR
{$now}\n[error:{$inputHash}]
STR;

      foreach (TELEGRAM_ERROR_REPORT_CHAT_ID as $chatId) {

      }

    }
  }

  /**
   * @param int    $chatId
   * @param string $reportData
   * @param string $inputData
   * @return void
   */
  public static function sendReport(int $chatId, string $reportData, string $inputData): void
  {

  }
}
