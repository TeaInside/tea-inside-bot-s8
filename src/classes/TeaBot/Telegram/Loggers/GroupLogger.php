<?php

namespace TeaBot\Telegram\Loggers;

use DB;
use PDO;
use PDOException;
use TeaBot\Telegram\Data;
use TeaBot\Telegram\Logger;
use TeaBot\Telegram\LoggerFoundation;
use TeaBot\Telegram\Contracts\LoggerInterface;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Loggers
 * @version 8.0.0
 */
class GroupLogger extends LoggerFoundation
{
  /**
   * @param string
   */
  public function execute()
  {
  	switch ($this->data["msg_type"]) {

  	}
  }
}
