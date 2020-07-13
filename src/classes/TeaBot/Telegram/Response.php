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
   * @throws \Exception
   * @param string $className
   * @param string $methodName
   * @param array  $parameters
   * @return bool
   */
  private function rtExec(
    string $className, string $methodName, array $parameters = []): bool
  {
    $obj = new $className($this->data);
    if ($obj instanceof ResponseFoundation) {
      return $obj->{$methodName}(...$parameters);
    } else {
      throw new Exception("Invalid ResponseFoundation instance: ".$className);
    }
  }
}
