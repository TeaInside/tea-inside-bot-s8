<?php

namespace TeaBot\Telegram;

use DB;
use PDO;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
abstract class LoggerFoundation
{

  /** 
   * @var \PDO
   */
  protected PDO $logger;

  /**
   * @var \TeaBot\Telegram\Data
   */
  protected Data $data;

  /**
   * @param \TeaBot\Telegram\Data  $data
   *
   * Constructor.
   */
  public function __construct(Data $data)
  {
    $this->pdo  = DB::pdo();
    $this->data = $data;
  }

  /**
   * @return void
   */
  abstract public function run(): void;
}
