<?php

namespace TeaBot\Telegram;

use Exception;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class Response
{
  use ResponseRoutes;

  /** 
   * @var \TeaBot\Telegram\Data
   */
  private $data;

  /**
   * @param \TeaBot\Telegram\Data
   *
   * Constructor.
   */
  public function __construct(Data $data)
  {
    $this->data = $data;
  }

  /**
   * @param string $class
   * @param string $method
   * @param array  $params
   * @return bool
   * @throws \Exception
   */
  public function rtExec(string $class, string $method, array $params = []): bool
  {
    $obj = new $class($this->data);
    if ($obj instanceof ResponseFoundation) {
      return $obj->{$method}(...$params);
    } else {
      throw new Exception("Invalid ResponseFoundation instance: ".$class);
    }
  }
}
