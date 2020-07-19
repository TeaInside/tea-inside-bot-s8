<?php

namespace TeaBot\Telegram;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
trait ResponseRoutes
{
  /**
   * @return bool
   */
  public function execRoutes(): bool
  {
    /* Start command. */
    if (preg_match("/^(\/|\!|\~|\.)start$/USsi", $this->data["text"])) {
      if ($this->rtExec(Responses\Start::class, "start")) {
        return true;
      }
    }

    /* Debug command. */
    if (preg_match("/^(\/|\!|\~|\.)debug$/USsi", $this->data["text"])) {
      if ($this->rtExec(Responses\Debug::class, "debug")) {
        return true;
      }
    }

    return false;
  }
}
