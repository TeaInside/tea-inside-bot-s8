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
  private $data;

  /**
   * @var \TeaBot\Telegram\TeaBot
   */
  private $teaBot;

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
   * @return void
   */
  public function run()
  {
    if (isset($this->data["msg_type"])) {

      if ($this->data["chat_type"] == "private") {
        $logger = new PrivateLogger($this->data);
      } else {
        $logger = new GroupLogger($this->data);
      }

      switch ($this->data["msg_type"]) {
        case "text":
          $logger->logText();
          break;
        case "photo":
          $logger->logPhoto();
          break;
        case "sticker":
          $logger->logSticker();
          break;

        default:
          // Unknown type of message.
          break;
      }
    }
  }
}
