<?php

namespace TeaBot\Telegram\Captcha\Entry;

use TeaBot\Telegram\Captcha\CaptchaFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Captcha
 * @version 8.0.0
 */
abstract class CaptchaEntry extends CaptchaFoundation
{
  /**
   * @return bool
   */
  abstract public function run(): bool;
}
