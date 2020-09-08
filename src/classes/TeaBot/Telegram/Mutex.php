<?php

namespace TeaBot\Telegram;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class Mutex
{

  /**
   * @var string
   */
  private string $tableName;


  /**
   * @var ?string
   */
  private ?string $uniqueId;


  /**
   * Constructor.
   *
   * @param string $tableName
   * @param string $uniqueId
   */
  public function __construct(string $tableName, ?string $uniqueId = null)
  {
    $this->lockFile = TELEGRAM_MUTEXES_LOCK_DIR."/{$tableName}";

    is_dir(TELEGRAM_MUTEXES_LOCK_DIR) or mkdir(TELEGRAM_MUTEXES_LOCK_DIR);

    if (is_string($uniqueId)) {
      is_dir($this->lockFile) or mkdir($this->lockFile);
      $this->lockFile .= "/{$uniqueId}.lock";
    } else {
      $this->lockFile .= ".lock";
    }

    $this->handle = fopen($this->lockFile, "w");
  }


  /**
   * @return mixed
   */
  public function lock()
  {
    return flock($this->handle, LOCK_EX);
  }


  /**
   * @return mixed
   */
  public function unlock()
  {
    return flock($this->handle, LOCK_UN);
  }


  /**
   * Destructor.
   */
  public function __destruct()
  {
    fclose($this->handle);
  }
}
