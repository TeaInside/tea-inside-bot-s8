<?php

namespace TeaBot\Telegram;

use ArrayAccess;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class Data implements ArrayAccess
{
  /**
   * @const int
   */
  public const MSG_TYPE_GENERAL = 1;

  /**
   * @var array
   */
  public $in;

  /**
   * @var array
   */
  public $ct;

  /**
   * @param array &$data
   *
   * Constructor.
   */
  public function __construct(array &$data)
  {
    $this->in = $data;
    $this->ct["in"] = &$this->in;

    if (isset($this->in["message"]["photo"])) {
      $this->ct["photo"] = &$this->in["message"]["photo"];
      $this->ct["text"] = &$this->in["message"]["caption"];
      $this->ct["msg_type"] = "photo";
      $this->buildGeneralMessage();
    } else if (isset($this->in["message"]["text"])) {
      $this->ct["text"] = &$this->in["message"]["text"];
      $this->ct["msg_type"] = "text";
      $this->buildGeneralMessage();
    } else if (isset($this->in["message"]["new_chat_members"])) {
      $this->ct["chat_id"] = &$this->in["message"]["chat"]["id"];
      $this->ct["msg_id"] = &$this->in["message"]["message_id"];
      $this->ct["msg_type"] = "new_chat_member";
      $this->ct["new_chat_members"] = $this->in["message"]["new_chat_members"];
    }
  }

  /**
  * @return void
  */
  private function buildGeneralMessage()
  {
    $this->ct["event_type"] = self::MSG_TYPE_GENERAL;

    if (isset($this->in["message"]["from"]["username"])) {
      $this->ct["username"] = &$this->in["message"]["from"]["username"];
    } else {
      $this->ct["username"] = null;
    }

    // if (isset($this->in["message"]["from"]["language_code"])) {
    //   $this->ct["lang"] = &$this->in["message"]["from"]["language_code"];
    //   Lang::init($this->ct["lang"]);
    // } else {
    //   $this->ct["lang"] = null;
    //   Lang::init("en");
    // }

    $this->ct["update_id"] = &$this->in["update_id"];
    $this->ct["msg_id"] = &$this->in["message"]["message_id"];
    $this->ct["chat_id"] = &$this->in["message"]["chat"]["id"];
    $this->ct["chat_title"] = &$this->in["message"]["chat"]["title"];
    $this->ct["user_id"] = &$this->in["message"]["from"]["id"];
    $this->ct["is_bot"] = &$this->in["message"]["from"]["is_bot"];
    $this->ct["first_name"] = &$this->in["message"]["from"]["first_name"];
    $this->ct["date"] = &$this->in["message"]["date"];
    $this->ct["reply"] = &$this->in["message"]["reply_to_message"];

    if (isset($this->in["message"]["from"]["last_name"])) {
      $this->ct["last_name"] = &$this->in["message"]["from"]["last_name"];
    } else {
      $this->ct["last_name"] = null;
    }

    if (isset($this->in["message"]["entities"])) {
      $this->ct["entities"] = &$this->in["message"]["entities"];
    } else {
      $this->ct["entities"] = null;
    }

    if ($this->in["message"]["chat"]["type"] === "private") {
      $this->ct["chat_type"] = "private";
    } else {
      $this->ct["chat_type"] = "group";
      $this->ct["group_name"] = &$this->in["message"]["chat"]["title"];
      $this->ct["group_username"] = &$this->in["message"]["chat"]["username"];
    }
  }

  /**
   * @param mixed $key
   * @return &mixed
   */
  public function &offsetGet($key)
  {
    if (!array_key_exists($key, $this->ct)) {
      $this->ct[$key] = null;
    }
    return $this->ct[$key];
  }

  /**
   * @param mixed $key
   * @param mixed &$data
   * @return void
   */
  public function offsetSet($key, $data)
  {
    $this->ct[$key] = $data;
  }

  /**
   * @param mixed $key
   * @return bool
   */
  public function offsetExists($key): bool
  {
    return isset($this->ct[$key]);
  }

  /**
   * @param mixed $key
   * @return void
   */
  public function offsetUnset($key)
  {
    unset($this->ct[$key]);
  }
}
