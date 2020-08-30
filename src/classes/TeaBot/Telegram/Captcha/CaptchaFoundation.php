<?php

namespace TeaBot\Telegram\Captcha;

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
 * @package \TeaBot\Telegram\Captcha
 * @version 8.0.0
 */
abstract class CaptchaFoundation
{
  /**
   * @var \TeaBot\Telegram\Data
   */
  protected Data $data;

  /**
   * @var string
   */
  protected string $captchaStDir;

  /**
   * @var string
   */
  protected string $answer;

  /**
   * @var string
   */
  protected string $lockFile;

  /**
   * @var resource
   */
  protected $lockHandle = null;

  /**
   * @var resource
   */
  protected $fastLockHandle = null;

  /**
   * @var bool
   */
  protected bool $dontBuildDir = false;

  /**
   * @var string
   */
  protected string $fastLock;

  /**
   * @param \TeaBot\Telegram\Data $data
   *
   * Constructor.
   */
  public function __construct(Data $data)
  {
    $this->data         = $data;

    $fixChatId          = str_replace("-", "_", $data["chat_id"]);
    $chatIdDir          = TELEGRAM_STORAGE_PATH."/captcha/".$fixChatId;
    $this->captchaStDir = $chatIdDir."/".$data["user_id"];
    $this->delMsgDir    = $this->captchaStDir."/d";

    $this->captchaFile  = $this->captchaStDir."/info.json";
    $this->lockFile     = $this->captchaStDir."/lock";

    $this->fastLockDir  = TELEGRAM_STORAGE_PATH."/captcha_fastlock";
    $this->fastLockFile = $this->fastLockDir."/".bin2hex($data["chat_id"].$data["user_id"]);

    $this->buildFastLockDir();

    if (!$this->dontBuildDir) {
      $this->buildDir();
    }
  }

  /**
   * @return void
   */
  public function addDeleteMsg(int $msgId): void
  {
    touch($this->delMsgDir."/".$msgId);
  }

  /**
   * @return void
   */
  public function buildFastLockDir(): void
  {
    $dirs        = explode("/", $this->fastLockDir);
    $mkdirTarget = "/";

    foreach ($dirs as $k => $v) {
      $mkdirTarget .= ($k ? "/" : "").$v;
      is_dir($mkdirTarget) or mkdir($mkdirTarget);
    }
  }

  /**
   * @return void
   */
  public function buildDir(): void
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
   * @param array $data
   * @return void
   * @throws \Exception
   */
  public function writeCaptchaFile(array $data): void
  {
    if (!isset(
      $data["chat_id"], $data["user_id"],
      $data["msg_id"], $data["correct_answer"]
    )) {
      throw new \Exception("Invalid captcha data!");
    }

    file_put_contents(
      $this->captchaFile,
      json_encode($data, JSON_UNESCAPED_SLASHES)
    );
  }

  /**
   * @return bool
   */
  public function isHavingCaptcha(): bool
  {
    $this->fastLock();
    $ret = file_exists($this->captchaFile);
    $this->fastUnlock();
    return $ret;
  }

  /**
   * @return void
   */
  public function lock(): void
  {
    $this->lockHandle = fopen($this->lockFile, "a+");
    flock($this->lockHandle, LOCK_EX);
  }

  /**
   * @return void
   */
  public function unlock(): void
  {
    if ($this->lockHandle) {
      flock($this->lockHandle, LOCK_UN);
    }
  }

  /**
   * @return void
   */
  public function fastLock(): void
  {
    $this->fastLockHandle = fopen($this->fastLockFile, "a+");
    flock($this->fastLockHandle, LOCK_EX);
  }

  /**
   * @return void
   */
  public function fastUnlock(): void
  {
    if ($this->fastLockHandle) {
      flock($this->fastLockHandle, LOCK_UN);
    }
    @unlink($this->fastLockFile);
  }

  /**
   * @return void
   */
  public function close(): void
  {
    fclose($this->lockHandle);
    $this->lockHandle = null;
  }

  /**
   * Destructor.
   */
  public function __destruct()
  {
    if ($this->lockHandle) {
      fclose($this->lockHandle);
    }
  }

  /**
   * @return void
   */
  public function cleanUpMessages(): void
  {
    if (!$this->isHavingCaptcha()) {
      return;
    }

    /* Get captcha information. */
    $json    = json_decode(file_get_contents($this->captchaFile), true);
    $ccMsgId = null;
    $chatId  = $this->data["chat_id"];

    if (isset($json["msg_id"])) {
      $ccMsgId = $json["msg_id"];

      /* Delete captcha message. */
      go(function () use ($chatId, $ccMsgId) {
        echo "\nDeleting {$chatId}:{$ccMsgId}...";
        Exe::deleteMessage([
          "chat_id"    => $chatId,
          "message_id" => $ccMsgId,
        ]);
        echo "\nDeleted {$chatId}:{$ccMsgId}!";
      });

      @unlink($this->delMsgDir."/".$ccMsgId);
    }

    $messageIds = scandir($this->delMsgDir);

    /* Delete captcha answers. */
    foreach ($messageIds as $k => $v) {
      if ($v[0] !== ".") {
        go(function () use ($chatId, $v) {
          echo "\nDeleting {$chatId}:{$v}...";
          Exe::deleteMessage([
            "chat_id"    => $chatId,
            "message_id" => $v,
          ]);
          echo "\nDeleted {$chatId}:{$v}!";
        });
        @unlink($this->delMsgDir."/".$v);
      }
    }
  }

  /**
   * @return void
   */
  public function captchaFailKick(): void
  {
    $this->fastLock();

    $d   = $this->data;
    $ret = Exe::kickChatMember(
      [
        "chat_id" => $d["chat_id"],
        "user_id" => $d["user_id"]
      ]
    );
    $ret = json_decode($ret->getBody()->__toString(), true);

    if (isset($ret["ok"], $ret["result"]) && $ret["ok"] && $ret["result"]) {
      $text  =
        "<a href=\"tg://user?id={$d["user_id"]}\">".e($d["full_name"])."</a>"
        .(isset($d["username"]) ? " (@{$d["username"]})" : "")
        ." has been kicked from the group due to failed to answer the captcha.";
      $ret = Exe::sendMessage(
        [
          "chat_id"    => $d["chat_id"],
          "text"       => $text,
          "parse_mode" => "HTML",
        ]
      );
      $ret = json_decode($ret->getBody()->__toString(), true);
      $kickMsgId = $ret["result"]["message_id"] ?? null;
    }

    $this->fastUnlock();

    $this->cleanUpMessages();

    sleep(30);

    Exe::unbanChatMember(
      [
        "chat_id" => $d["chat_id"],
        "user_id" => $d["user_id"]
      ]
    );

    if (isset($kickMsgId)) {
      sleep(120);
      Exe::deleteMessage(["chat_id" => $d["chat_id"], "message_id" => $msgId]);
      Exe::deleteMessage(["chat_id" => $d["chat_id"], "message_id" => $d["msg_id"]]);
    }
  }
}
