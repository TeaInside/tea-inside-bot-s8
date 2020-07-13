<?php

namespace TeaBot\Telegram;

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
   * @return bool
   */
  public function execute(): bool
  {
    
  }
}
