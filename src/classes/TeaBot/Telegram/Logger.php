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
  private ?Data $data;

  /**
   * @var \TeaBot\Telegram\TeaBot
   */
  private ?TeaBot $teaBot;

  /**
   * @param ?\TeaBot\Telegram\TeaBot $teaBot
   * @param ?\TeaBot\Telegram\Data   $data
   *
   * Constructor.
   */
  public function __construct(?TeaBot $teaBot = null, ?Data $data = null)
  {
    $this->teaBot = $teaBot;
    if ($data instanceof Data) {
      $this->data = $data;
    } else {
      $this->data = $teaBot->data ?? null;
    }
  }

  /**
   * @param mixed $key
   * @return mixed
   */
  public function __get($key)
  {
    return $this->{$key} ?? null;
  }

  /** 
   * @return bool
   */
  public function run(): bool
  {
    if (isset($this->data["msg_type"], $this->data["chat_type"])) {
      var_dump($this->data["chat_type"]);
      if ($this->data["chat_type"] === "private") {
        $logger = new PrivateLogger($this);
      } else {
        $logger = new GroupLogger($this);
      }

      return $logger->execute();
    }
    return false;
  }
}
