<?php

namespace TeaBot\Telegram;

use DB;
use PDO;
use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Exceptions\LoggerException;
use TeaBot\Telegram\LoggerFoundationTraits\FileResolver;
use TeaBot\Telegram\LoggerFoundationTraits\UserResolver;
use TeaBot\Telegram\LoggerFoundationTraits\GroupResolver;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
abstract class LoggerFoundation
{
  use FileResolver, UserResolver, GroupResolver;

	/**
	 * @var \TeaBot\Telegram\Logger
	 */
	protected Logger $logger;

  /**
   * @var \TeaBot\Telegram\Data
   */
  protected Data $data;

  /**
   * @param \TeaBot\Telegram\Logger $logger
   */
	public function __construct(Logger $logger)
  {
    $this->logger = $logger;
    $this->data   = $logger->data;
	}

  /**
   * @param int $groupId
   * @return void
   */
  final public static function incrementGroupMsgCount(int $groupId): void
  {
    DB::pdo()
      ->prepare("UPDATE `tg_groups` SET `msg_count`=`msg_count`+1 WHERE `id`=?")
      ->execute([$groupId]);
  }

  /**
   * @param int $userId
   * @param int $type
   * @return void
   * @throws \TeaBot\Telegram\Exceptions\LoggerException
   */
  final public static function incrementUserMsgCount(int $userId, int $type): void
  {
    switch ($type) {
      case 1:
        DB::pdo()
          ->prepare("UPDATE `tg_users` SET `private_msg_count`=`private_msg_count`+1 WHERE `id`=?")
          ->execute([$userId]);
        break;
      case 2:
        DB::pdo()
          ->prepare("UPDATE `tg_users` SET `group_msg_count`=`group_msg_count`+1 WHERE `id`=?")
          ->execute([$userId]);
        break;
      default:
        throw new LoggerException("Invalid type: {$type}");
        break;
    }
  }

  /**
   * @param string $tgFileId
   * @param bool   $addHitCount
   * @param bool   $transactional
   * @throws \PDOException
   * @return ?int
   */
  final public static function fileResolve(
    string $tgFileId,
    bool $addHitCount = false,
    bool $transactional = false
  ): ?int
  {
    if ($transactional) {
      $trx = DB::transaction(function (PDO $pdo) use ($tgFileId, $addHitCount) {
        return self::baseFileResolve($tgFileId, $addHitCount);
      });
      /*debug:7*/
      $trx->setName("baseFileResolve");
      /*enddebug*/
      $trx->setErrorCallback(function (PDO $pdo, $e) {
        throw $e;
      });
      $trx->setDeadlockTryCount(10);
      $trx->setTrySleep(rand(1, 5));
      if (!$trx->execute()) {
        return null;
      }
      return $trx->getRetVal();
    } else {
      return self::baseFileResolve($tgFileId, $addHitCount);
    }
  }

  /**
   * @param array $data
   * @throws \PDOException
   * @return ?int
   */
  public static function groupInsert(array $data): ?int
  {
    /**
     * Information about $action
     * @see TeaBot\Telegram\LoggerFoundationTraits\GroupResolver::baseGroupInsert
     */
    $action = -1;
    $moreFetch = false;
    $errCallback = function (PDO $pdo, $e) {
      throw $e;
    };

    $trx = DB::transaction(function (PDO $pdo) use (&$data, &$moreFetch, &$action) {
      return self::baseGroupInsert($data, $moreFetch, $action);
    });
    /*debug:7*/
    $trx->setName("baseGroupInsert");
    /*enddebug*/
    $trx->setErrorCallback($errCallback);
    $trx->setDeadlockTryCount(10);
    $trx->setTrySleep(rand(1, 5));
    if (!$trx->execute()) {
      return null;
    }
    $retVal = $trx->getRetVal();
    if (!$retVal) {
      $retVal = null;
    }

    /*
     * In some conditions, we need to fetch photo and group admins.
     */
    if ($moreFetch) {

      /*
       * Don't fetch photo and group admins in transaction.
       */
      $data["photo"] = self::getLatestGroupPhoto($data["tg_group_id"]);
      self::groupAdminResolve($data["tg_group_id"], $retVal);

      $trx = DB::transaction(
        function (PDO $pdo) use (&$data, $moreFetch, $action): bool {
          return self::baseGroupInsert($data, $moreFetch, $action);
        }
      );
      /*debug:7*/
      $trx->setName("updateGroupInfo");
      /*enddebug*/
      $trx->setErrorCallback($errCallback);
      $trx->setDeadlockTryCount(10);
      $trx->setTrySleep(rand(1, 5));
      $trx->execute();
    }
    return $retVal;
  }


  /**
   * @param array $data
   * @param bool  $transactional
   * @throws \PDOException
   * @return ?int
   */
  public static function userInsert(array $data, bool $transactional = true): ?int
  {

    /**
     * Information about $action
     * @see TeaBot\Telegram\LoggerFoundationTraits\GroupResolver::baseUserInsert
     */
    $action = -1;
    $moreFetch = false;

    if (!$transactional) {
      return self::baseUserInsert($data, $moreFetch, $action);
    }

    $errCallback = function (PDO $pdo, $e) {
      throw $e;
    };

    $trx = DB::transaction(function (PDO $pdo) use (&$data, &$moreFetch, &$action) {
      return self::baseUserInsert($data, $moreFetch, $action);
    });
    /*debug:7*/
    $trx->setName("baseUserInsert");
    /*enddebug*/
    $trx->setErrorCallback($errCallback);
    $trx->setDeadlockTryCount(10);
    $trx->setTrySleep(rand(1, 5));
    if (!$trx->execute()) {
      return null;
    }
    $retVal = $trx->getRetVal();
    if (!$retVal) {
      $retVal = null;
    }

    /*
     * In some conditions, we need to fetch photo and group admins.
     */
    if ($moreFetch) {

      /*
       * Don't fetch photo and group admins in transaction.
       */
      $data["photo"] = self::getLatestUserPhoto($data["tg_user_id"]);

      $trx = DB::transaction(
        function (PDO $pdo) use (&$data, $moreFetch, $action): bool {
          return self::baseUserInsert($data, $moreFetch, $action);
        }
      );
      /*debug:7*/
      $trx->setName("updateUserInfo");
      /*enddebug*/
      $trx->setErrorCallback($errCallback);
      $trx->setDeadlockTryCount(10);
      $trx->setTrySleep(rand(1, 5));
      $trx->execute();
    }
    return $retVal;
  }

}
