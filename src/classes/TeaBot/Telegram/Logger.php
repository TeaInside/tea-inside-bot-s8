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
   * @var \TeaBot\Telegram\TeaBot
   */
  private TeaBot $teaBot;

  /**
   * @param \TeaBot\Telegram\Data
   *
   * Constructor.
   */
  public function __construct(TeaBot $teaBot)
  {
    $this->teaBot = $teaBot;
    $this->data   = $teaBot->data;
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
   * @return void
   */
  public function run()
  {
    if (isset($this->data["msg_type"], $this->data["chat_type"])) {

      if ($this->data["chat_type"] === "private") {
        $logger = new PrivateLogger($this);
      } else {
        $logger = new GroupLogger($this);
      }

      $logger->execute();
    }
  }
}
