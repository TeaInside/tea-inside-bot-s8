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

      if (!$this->captcha($captcha = new $captchaClass($this->data))) {
        // Failed to answer the captcha.
        $ret = Exe::kickChatMember(
          [
            "chat_id" => $this->data["chat_id"],
            "user_id" => $this->data["user_id"]
          ]
        );

        $ret = json_decode($ret->getBody()->__toString(), true);

        $captcha->cleanUpOldCaptcha();

        if (isset($ret["ok"], $ret["result"]) && $ret["ok"] && $ret["result"]) {
          $uname = isset($this->data["username"]) ? " (@{$this->data["username"]})" : "";

          $text  = "<a href=\"tg://user?id={$this->data["user_id"]}\">"
            .htmlspecialchars($name, ENT_QUOTES, "UTF-8")."</a>"
            .$uname
            ." has been kicked from the group due to failed to answer the captcha.";

          Exe::sendMessage(
            [
              "chat_id"    => $this->data["chat_id"],
              "text"       => $text,
              "parse_mode" => "HTML",
            ]
          );
        }

        Exe::unbanChatMember(
          [
            "chat_id" => $chatId,
            "user_id" => $userId
          ]
        );
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
