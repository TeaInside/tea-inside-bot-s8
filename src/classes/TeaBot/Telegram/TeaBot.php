<?php

namespace TeaBot\Telegram;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class TeaBot
{
  /**
   * @var \TeaBot\Telegram\Data
   */
  private $data;

  /**
   * @param array &$data
   *
   * Constructor.
   */
  public function __construct(array &$data)
  {
    $this->data = new Data($data);
  }
}
