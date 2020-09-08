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
    return $this->{$key};
  }

  const MSG_TYPE_MAP = [
    "text"      => true,
    // "photo"     => true,
    // "sticker"   => true,
    // "animation" => true,
    // "voice"     => true,
    // "video"     => true,
  ];

  const GROUP_CHAT_MAP = [
    "supergroup" => true,
  ];

  /**
   * @return void
   */
  public function run(): void
  {
    $data = $this->data;

    /* Skip if msg_type is not mapped. */
    if (!isset(self::MSG_TYPE_MAP[$data["msg_type"]])) {
      return;
    }

    if ($data["chat_type"] === "private") {
      $logger = new PrivateLogger($data);
    } else
    if (isset(self::GROUP_CHAT_MAP[$data["chat_type"]])) {
      $logger = new GroupLogger($data);
    } else {
      $logger = null;
    }

    if ($logger) {
      $logger->run();
    }

  }
}
