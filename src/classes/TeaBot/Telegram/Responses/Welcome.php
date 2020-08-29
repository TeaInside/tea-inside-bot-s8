<?php

namespace TeaBot\Telegram\Responses;

use TeaBot\Telegram\Exe;
use TeaBot\Telegram\ResponseFoundation;
use TeaBot\Telegram\Responses\Welcome\Captcha\CaptchaFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Responses
 * @version 8.0.0
 */
final class Welcome extends ResponseFoundation
{
  /**
   * @return bool
   */
  public function welcome(): bool
  {

    if ($this->data["chat_id"] === -1001226735471) {
      $captchaType  = "ComputerScience\\FloatingPoint";
      $captchaClass = "\\TeaBot\\Telegram\\Responses\\Welcome\\Captcha\\".$captchaType;

      if ($this->captcha(new $captchaClass($this->data))) {
        // Captcha answered correctly.
      } else {
        // Failed to answer the captcha.
      }
    }


    return true;
  }

  /**
   * @param \TeaBot\Telegram\Responses\Welcome\Captcha\CaptchaFoundation $captchaClass
   * @return bool
   */
  private function captcha(CaptchaFoundation $captcha): bool
  {
    return $captcha->run();
  }
}
