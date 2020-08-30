<?php

namespace TeaBot\Telegram\Responses\Welcome\Captcha;

use Swlib\Saber;
use Swlib\Http\Uri;
use Swlib\Http\ContentType;
use Swlib\Http\BufferStream;
use Swlib\Http\Exception\ConnectException;
use Swlib\Http\Exception\TransferException;

use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Data;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Responses\Welcome\Captcha
 * @version 8.0.0
 */
abstract class CaptchaFoundation
{
  /**
   * @var \TeaBot\Telegram\Data
   */
  protected $data;

  /**
   * @var string
   */
  protected $captchaStDir;

  /**
   * @var string
   */
  protected $answer;

  /**
   * @var bool
   */
  protected $dontBuildDir = false;

  /**
   * @param \TeaBot\Telegram\Data $data
   */
  public function __construct(Data $data)
  {
    $this->data         = $data;
    $fixChatId          = str_replace("-", "_", $data["chat_id"]);
    $this->captchaStDir =
      TELEGRAM_STORAGE_PATH."/captcha/".$fixChatId."/".$data["user_id"];
    $this->delMsgDir    = $this->captchaStDir."/d";
    $this->captchaFile  = $this->captchaStDir."/info.json";

    if (!$this->dontBuildDir) {
      $this->buildDir();
    }
  }

  /**
   * @param int $msgId
   */
  public function addDeleteMsg(int $msgId)
  {
    touch($this->delMsgDir."/".$msgId);
  }

  /**
   * @return void
   */
  public function cleanUpOldCaptcha(): void
  {
    if (!$this->isHavingCaptcha()) {
      return;
    }

    $json    = json_decode(file_get_contents($this->captchaFile), true);
    $ccMsgId = null;
    $chatId  = $this->data["chat_id"];

    if (isset($json["msg_id"])) {
      $ccMsgId = $json["msg_id"];
      go(function () use ($chatId, $ccMsgId) {
        echo "\nDeleting {$chatId}:{$ccMsgId}...";
        Exe::deleteMessage([
          "chat_id"    => $chatId,
          "message_id" => $ccMsgId,
        ]);
        echo "\nDelete {$chatId}:{$ccMsgId} OK!";
      });
      @unlink($this->delMsgDir."/".$ccMsgId);
    }

    $messageIds = scandir($this->delMsgDir);

    foreach ($messageIds as $k => $v) {
      if ($v[0] !== ".") {
        go(function () use ($chatId, $v) {
          echo "\nDeleting {$chatId}:{$v}...";
          Exe::deleteMessage([
            "chat_id"    => $chatId,
            "message_id" => $v,
          ]);
          echo "\nDelete {$chatId}:{$v} OK!";
        });
        @unlink($this->delMsgDir."/".$v);
      }
    }
  }

  /**
   * @return void
   */
  protected function buildDir(): void
  {
    $dirs        = explode("/", $this->captchaStDir);
    $mkdirTarget = "/";

    foreach ($dirs as $k => $v) {
      $mkdirTarget .= ($k ? "/" : "").$v;
      is_dir($mkdirTarget) or mkdir($mkdirTarget);
    }

    is_dir($this->delMsgDir) or mkdir($this->delMsgDir);
  }

  /**
   * @return bool
   */
  public function isHavingCaptcha(): bool
  {
    return file_exists($this->captchaFile);
  }

  /**
   * @return bool
   */
  abstract public function run(): bool;

  /**
   * @param string  $text
   * @param string  $photoUrl
   * @param ?string $parseMode
   * @return mixed
   */
  protected function sendCaptchaPhoto(string $text, string $photoUrl, ?string $parseMode = null)
  {
    return Exe::sendPhoto(
      [
        "chat_id"             => $this->data["chat_id"],
        "caption"             => $text,
        "photo"               => $photoUrl,
        "reply_to_message_id" => $this->data["msg_id"],
        "parse_mode"          => $parseMode,
      ]
    );
  }

  /**
   * @param string $latex
   * @param string $bcolor
   * @param string $border
   * @param int    $d
   */
  protected function genLatex(
    string $latex, string $bcolor = "white", $border = "60x60", $d = 200): ?string
  {
    $ret = null;
    $payload = [
      "bcolor"  => $bcolor,
      "border"  => $border,
      "content" => $latex,
      "d" => $d
    ];

    $tryCounter = 0;

    try_ll:
    try {

      $tryCounter++;
      $saber = Saber::create([
        "base_uri" => "https://latex.teainside.org",
        "headers"  => ["Content-Type" => ContentType::JSON],
        "timeout"  => 500,
      ]);
      $rrr = $saber->post("/api.php?action=tex2png", $payload);

    } catch (TransferException $e) {

      $rrr = $e->getResponse();

      if (is_null($rrr) && ($tryCounter <= 5)) goto try_ll;

    } catch (ConnectException $e) {
      if ($tryCounter <= 5) goto try_ll;
    }

    $o = json_decode($rrr->getBody()->__toString(), true);
    if (isset($o["res"])) {
      $ret = "https://latex.teainside.org/latex/png/".$o["res"].".png";
    } else {
      echo "Cannot generate captcha: ".$o;
    }

    return $ret;
  }
}
