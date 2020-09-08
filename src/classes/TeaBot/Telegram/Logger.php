<?php

namespace TeaBot\Telegram;

use DB;
use PDO;
use TeaBot\Telegram\Loggers\GroupLogger;
use TeaBot\Telegram\Loggers\PrivateLogger;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class Logger
{
  /** 
   * @var \TeaBot\Telegram\Data
   */
  private Data $data;

  /**
   * @var \PDO
   */
  private PDO $pdo;

  /**
   * @param array $data
   *
   * Constructor.
   */
  public function __construct(array $data)
  {
    $this->data = new Data($data);
  }

  /**
   * @param mixed $key
   */
  public function __get($key)
  {
    if (($key === "pdo") && (!$this->pdo)) {
      return ($this->pdo = DB::pdo());
    }

    return $this->{$key};
  }

  /**
   * @return void
   */
  public function run(): void
  {

  }
}
