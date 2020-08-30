<?php

namespace TeaBot\Telegram\Captcha;

use Exception;
use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Data;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Captcha
 * @version 8.0.0
 */
class CaptchaRuntime extends CaptchaFoundation
{
  /**
   * @param \TeaBot\Telegram\Data $data
   *
   * Constructor.
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
    $this->fastLock();
    $this->lock();

    $d = $this->data;

    /* Load captcha file. */
    $json = json_decode(file_get_contents($this->captchaFile), true);
    $text = strtolower(trim($d["text"]));

    if ($text === $json["correct_answer"]) {

      @unlink($this->captchaFile);

      $text  =
        "<a href=\"tg://user?id={$d["user_id"]}\">".e($d["full_name"])."</a>"
        .(isset($d["username"]) ? " (@{$d["username"]})" : "")
        ."  has answered the captcha correctly, welcome to the group!";
      $ret = Exe::sendMessage(
        [
          "chat_id"    => $d["chat_id"],
          "text"       => $text,
          "parse_mode" => "HTML",
        ]
      );
      $ret = json_decode($ret->getBody()->__toString(), true);

      var_dump("123123");
      $this->cleanUpMessages();

      if (isset($ret["result"]["message_id"])) {
        $msgId = $ret["result"]["message_id"];
      } else {
        $msgId = null;
      }

      if (isset($msgId)) {
        sleep(120);
        Exe::deleteMessage(["chat_id" => $d["chat_id"], "message_id" => $d["msg_id"]]);
        Exe::deleteMessage(["chat_id" => $d["chat_id"], "message_id" => $msgId]);
      }
    } else {
      $ret = Exe::sendMessage(
        [
          "chat_id"             => $d["chat_id"],
          "text"                => "Wrong answer!",
          "reply_to_message_id" => $d["msg_id"]
        ]
      );
      $ret = json_decode($ret->getBody()->__toString(), true);
      if (isset($ret["result"]["message_id"])) {
        $this->addDeleteMsg($ret["result"]["message_id"]);
      }
      $this->addDeleteMsg($d["msg_id"]);
    }

    $this->unlock();
    $this->fastUnlock();
  }

  /**
   * @return bool
   */
  public function needCaptcha(): bool
  {
    $this->fastLock();

    $ret = in_array($this->data["chat_id"],
      [
        -1001338293135, // PCX
        -1001226735471, // Private Cloud
      ]
    );

    $this->fastUnlock();
    return $ret;
  }

  /**
   * @return void
   */
  public function dropCaptcha(): void
  {
    $this->fastLock();
    $this->buildDir();
    touch($this->captchaFile);
    $this->fastUnlock();

    $this->lock();
    $this->cleanUpMessages();
    $captchaType = "ComputerScience\\FloatingPoint";
    $ccClass     = "TeaBot\\Telegram\\Captcha\\Entry\\".$captchaType;
    $ccClass     = new $ccClass($this->data);
    $ccClass->run();
    $this->unlock();
  }
}
