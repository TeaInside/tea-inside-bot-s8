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
class User extends LoggerUtilFoundation
{
/**
   * @param  int    $tgUserId
   * @param  ?array &$info
   * @param  ?bool  &$isInsert
   * @return ?int
   */
  public function resolveUser(int $tgUserId, ?array &$info = null, ?bool &$isInsert = null): ?int
  {
    $e     = null;
    $mutex = new Mutex("tg_users", "{$tgUserId}");
    $mutex->lock();

    /* debug:assert */
    if (is_array($info)) {
      $requiredFields = [
        "username",
        "first_name",
        "last_name",
        "is_bot",
      ];
      $missing = [];
      foreach ($requiredFields as $k => $v) {
        if (!array_key_exists($v, $info)) {
          $missing[] = $v;
        }
      }
      if (count($missing) > 0) {
        throw new \Error("Missing required fields: ".json_encode($missing));
      }
    }
    /* end_debug */

    $pdo = $this->pdo;

    try {

      $pdo->beginTransaction();

      if (is_array($info)) {
        $ret = $this->fullResolveUser($tgUserId, $info, $isInsert);
      } else {
        $ret = $this->directResolveUser($tgUserId);
      }

      $pdo->commit();
    } catch (Exception $e) {
      $pdo->rollback();
    }

    $mutex->unlock();

    if ($e) {
      echo $e->getMessage(), "\n";
      throw new $e;
    }

    return $ret;
  }
}
