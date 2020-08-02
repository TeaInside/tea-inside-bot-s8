<?php

namespace TeaBot\Telegram\Contracts;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
interface LoggerInterface
{
  /**
   * @return void
   */
  public function logText(): void;

  /**
   * @return void
   */
  public function logPhoto(): void;

  /**
   * @return void
   */
  public function logSticker(): void;

  /**
   * @return void
   */
  public function logAnimation(): void;

  /**
   * @return void
   */
  public function logVoice(): void;

  /**
   * @return void
   */
  public function logVideo(): void;
}
