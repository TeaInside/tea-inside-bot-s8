<?php

namespace TeaBot\Telegram\Responses\Welcome\Captcha;

use TeaBot\Telegram\Exe;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Responses\Welcome\Captcha
 * @version 8.0.0
 */
class CaptchaRuntime extends CaptchaFoundation
{
  /**
   * @return void
   */
  public function checkAnswer(): void
  {
    $handle = fopen($this->captchaFile, "r+");
    flock($handle, LOCK_EX);

    $json = "";
    while ($r = fread($handle, 8096)) $json .= $r;
    $json = json_decode($json, true);

    $answer = trim($this->data["text"]);

    if (isset($json["correct_answer"]) &&
       ($json["correct_answer"] === $answer)) {

      $name  = $this->data["first_name"]
        .(isset($this->data["last_name"]) ? " ".$this->data["last_name"] : "");
      
      $uname = isset($this->data["username"]) ? " (@{$this->data["username"]})" : "";

      $text  = "<a href=\"tg://user?id={$this->data["user_id"]}\">"
        .htmlspecialchars($name, ENT_QUOTES, "UTF-8")."</a>"
        .$uname
        ." has answered the captcha correctly, welcome to the group!";

      Exe::sendMessage(
        [
          "chat_id"    => $this->data["chat_id"],
          "text"       => $text,
          "parse_mode" => "HTML",
          "reply_to_message_id" => $this->data["msg_id"]
        ]
      );

    } else {

      Exe::sendMessage(
        [
          "chat_id"    => $this->data["chat_id"],
          "text"       => "Wrong answer!",
          "parse_mode" => "HTML",
          "reply_to_message_id" => $this->data["msg_id"]
        ]
      );

    }
    fclose($handle);
  }

  /**
   * @return bool
   */
  public function run(): bool
  {
    return true;
  }
}
