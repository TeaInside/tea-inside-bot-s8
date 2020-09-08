<?php

namespace TeaBot\Telegram;

use PDO;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
abstract class LoggerUtilFoundation
{

  /** 
   * @var \PDO
   */
  protected PDO $logger;

  /**
   * @param \PDO $pdo
   *
   * Constructor.
   */
  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }
}
