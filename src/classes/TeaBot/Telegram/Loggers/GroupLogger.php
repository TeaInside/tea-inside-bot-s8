<?php

namespace TeaBot\Telegram\Loggers;

use DB;
use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Data;
use TeaBot\Telegram\Dlog;
use TeaBot\Telegram\LoggerFoundation;
use TeaBot\Telegram\LoggerUtils\File;
use TeaBot\Telegram\LoggerUtils\User;
use TeaBot\Telegram\LoggerUtils\Group;
use TeaBot\Telegram\LoggerUtils\GroupMessage;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Loggers
 * @version 8.0.0
 */
class GroupLogger extends LoggerFoundation
{

  /**
   * @var \TeaBot\Telegram\LoggerUtils\User
   */
  private User $user;

  /**
   * @var \TeaBot\Telegram\LoggerUtils\Group
   */
  private Group $group;


  /**
   * Constructor.
   *
   * @param \Data $pdo
   */
  public function __construct(Data $data)
  {
    parent::__construct($data);
    $this->user = new User($this->pdo);
    $this->user->setData($data);
    $this->group = new Group($this->pdo);
    $this->group->setData($data);
  }


  /**
   * @return void
   */
  public function run(): void
  {
    $user    = $this->user;
    $userII  = $user->resolveUser();

    if (!($userII["private_msg_count"] % 10)) {
      /* Track user photo. */
      go(function () use ($user) {
        $user->setPDO(DB::pdo());
        $user->trackPhoto();
      });
    }

    $group   = $this->group;
    $groupII = $group->resolveGroup();

    if (!($groupII["msg_count"] % 10)) {
      /* Track group photo. */
      $group->dropPDO();
      $group2 = clone $group;

      go(function () use ($group) {
        $group->setPDO(DB::pdo());
        $group->trackPhoto();
      });

      go(function () use ($group2) {
        $group2->setPDO(DB::pdo());
        $group2->trackAdmins();
      });

      unset($group2);
    }
  }
}
