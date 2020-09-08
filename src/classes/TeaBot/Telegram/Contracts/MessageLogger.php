<?php

namespace TeaBot\Telegram\Contracts;

use Exception;
use ArrayAccess;
use TeaBot\Telegram\Data;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Contracts
 * @version 8.0.0
 */
interface MessageLogger
{
  /**
   * @param int                   $msgId
   * @param \TeaBot\Telegram\Data $data
   * @param string                $dateTime
   * @return bool
   */
  public function saveData(int $msgId, Data $data, string $dateTime): int;
}
