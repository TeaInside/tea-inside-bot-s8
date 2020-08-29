<?php

namespace TeaBot\Telegram;

use Exception;
use TeaBot\Telegram\Responses\Welcome\Captcha\CaptchaRuntime;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class Response
{
  use ResponseRoutes;

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
   *
   */
  public function run()
  {
    if (isset($this->data["text"])) {
      $ccRuntime = new CaptchaRuntime($this->data);

      if ($ccRuntime->isHavingCaptcha()) {
        $ccRuntime->checkAnswer();
      } else {
        unset($ccRuntime);
        $this->execRoutes();
      }

    } else
    if ($this->data["msg_type"] === "new_chat_member") {
      $this->rtExec(Responses\Welcome::class, "welcome");
    }
  }

  /**
   * @param string $class
   * @param string $method
   * @param array  $params
   * @return bool
   * @throws \Exception
   */
  public function rtExec(string $class, string $method, array $params = []): bool
  {
    $obj = new $class($this->data);
    if ($obj instanceof ResponseFoundation) {
      return $obj->{$method}(...$params);
    } else {
      throw new Exception("Invalid ResponseFoundation instance: ".$class);
    }
  }
}
