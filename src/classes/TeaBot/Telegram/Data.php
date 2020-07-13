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
   * @var array
   */
  public $in;

  /**
   * @param array &$data
   *
   * Constructor.
   */
  public function __construct(array &$data)
  {
    $this->in = $data;
  }

  /**
   * @param mixed $key
   * @return &mixed
   */
  public function &offsetGet($key)
  {
    if (!array_key_exists($key, $this->container)) {
      $this->container[$key] = null;
    }
    return $this->container[$key];
  }

  /**
   * @param mixed $key
   * @param mixed &$data
   * @return void
   */
  public function offsetSet($key, $data)
  {
    $this->container[$key] = $data;
  }

  /**
   * @param mixed $key
   * @return bool
   */
  public function offsetExists($key): bool
  {
    return isset($this->container[$key]);
  }

  /**
   * @param mixed $key
   * @return void
   */
  public function offsetUnset($key)
  {
    unset($this->container[$key]);
  }
}
