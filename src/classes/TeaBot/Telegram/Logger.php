<?php

namespace TeaBot\Telegram;

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
   * @param \TeaBot\Telegram\Data
   *
   * Constructor.
   */
  public function __construct(Data $data)
  {
    $this->data = $data;
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
      }
    }
  }
}
