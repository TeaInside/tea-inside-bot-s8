<?php

namespace TeaBot\Telegram;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
abstract class LoggerFoundation
{

  use LoggerUtils\User;

  /** 
   * @var \TeaBot\Telegram\Logger
   */
  private Logger $logger;

  /**
   * @param \TeaBot\Telegram\Logger $logger
   *
   * Constructor.
   */
  public function __construct(Logger $logger)
  {
    $this->logger = $logger;
  }
}
