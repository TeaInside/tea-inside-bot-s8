<?php

namespace TeaBot\Telegram\LoggerUtils;

use DB;
use PDO;
use Exception;
use TeaBot\Telegram\Mutex;
use TeaBot\Telegram\LoggerUtilFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\LoggerUtils
 * @version 8.0.0
 */
class Group extends LoggerUtilFoundation
{
  /**
   * @param  int    $tgUserId
   * @param  ?array info
   * @return ?int
   */
  public function resolveGroup(int $tgUserId, ?array $info = null): ?int
  {
  }
}
