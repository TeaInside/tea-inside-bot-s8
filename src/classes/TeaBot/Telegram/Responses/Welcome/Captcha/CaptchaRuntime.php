<?php

namespace TeaBot\Telegram\Responses\Welcome\Captcha;

use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Data;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Responses\Welcome\Captcha
 * @version 8.0.0
 */
class CaptchaRuntime extends CaptchaFoundation
{
  /**
   * @param \TeaBot\Telegram\Data $data
   */
  public function __construct(Data $data)
  {
    $this->dontBuildDir = true;
    parent::__construct($data);
  }

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

      $this->cleanUpOldCaptcha();
      @unlink($this->captchaFile);

      $msgId  = $this->data["msg_id"];
      $chatId = $this->data["chat_id"];

      $ret = Exe::sendMessage(
        [
          "chat_id"    => $chatId,
          "text"       => $text,
          "parse_mode" => "HTML",
          "reply_to_message_id" => $msgId,
        ]
      );

      $json = json_decode($ret->getBody()->__toString(), true);

      sleep(120);

      echo "\nDeleting {$chatId}:{$v}...";
      Exe::deleteMessage([
        "chat_id"    => $chatId,
        "message_id" => $msgId,
      ]);

      echo "\nDeleting {$chatId}:{$json["result"]["message_id"]}...";
      Exe::deleteMessage([
        "chat_id"    => $chatId,
        "message_id" => $json["result"]["message_id"],
      ]);

    } else {

      $this->addDeleteMsg($this->data["msg_id"]);

      $ret = Exe::sendMessage(
        [
          "chat_id"    => $this->data["chat_id"],
          "text"       => "Wrong answer!",
          "parse_mode" => "HTML",
          "reply_to_message_id" => $this->data["msg_id"]
        ]
      );

      $json = json_decode($ret->getBody()->__toString(), true);
      if (isset($json["result"]["message_id"])) {
        $this->addDeleteMsg($json["result"]["message_id"]);
      }
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
