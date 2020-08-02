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
   * @return ?int
   */
  final public static function fileResolve(
    string $tgFileId,
    bool $addHitCount = false,
    bool $transactional = false
  ): ?int
  {

    if (!$transactional) {
      return self::baseFileResolve($tgFileId, $addHitCount);
    }

    try {
      /*debug:5*/
      var_dump("beginTransaction: ".$cid);
      /*enddebug*/

      $pdo->beginTransaction();

      /*debug:5*/
      var_dump("beginTransaction OK: ".$cid);
      /*enddebug*/

      $fileId = self::baseFileResolve($tgFileId, $addHitCount);

      /*debug:5*/
      var_dump("commit: ".$cid);
      /*enddebug*/

      $pdo->commit();

    } catch (PDOException $e) {
      /*debug:5*/
      var_dump("rollback: ".$cid);
      var_dump($e."");
      /*enddebug*/

      $pdo->rollBack();
      $teaBot and $teaBot->errorReport($e);
      return null;
    } catch (Error $e) {
      /*debug:5*/
      var_dump("rollback: ".$cid);
      var_dump($e."");
      /*enddebug*/

      $pdo->rollBack();
      $teaBot and $teaBot->errorReport($e);
      return null;
    }

    return $fileId;
  }
}
