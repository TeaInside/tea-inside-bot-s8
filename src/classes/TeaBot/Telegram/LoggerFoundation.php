<?php

namespace TeaBot\Telegram;

use DB;
use PDO;
use TeaBot\Exe;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
abstract class LoggerInterface
{
  /**
   * @var \Data
   */
  protected $data;

  /**
   * @param \Data &$data
   *
   * Constructor.
   */
  final public function __construct(Data &$data)
  {
    $this->data = $data;
  }

  /**
   * @param string $telegramFileId
   * @return ?int
   */
  public static function fileResolve(string $telegramFileId): ?int
  {
    /**
     * Check the $telegramFileId in database.
     * If it has already been stored, then returns
     * the stored primary key. Otherwise, it
     * downloads the file and insert it to the
     * database.
     */



    $pdo = DB::pdo();
    $st  = $pdo->prepare("SELECT `id` FROM `tg_files` WHERE tg_file_id = ?");
    $st->execute([$telegramFileId]);

    if ($r = $st->fetch(PDO::FETCH_NUM)) {
      return (int)$r[0];
    }

    /**
     * This operation may error.
     */
    $v = json_decode(
      Exe::getFile(["file_id" => $telegramFileId])
      ->getBody()->__toString(),
      true
    );


    /**
     * Return null if it cannot find the file path.
     */
    if (!isset($v["result"]["file_path"])) {
      return null;
    }


    /**
     *  Get file extension.
     */
    $fileExt = explode(".", $v["result"]["file_path"]);
    if (count($fileExt) > 1) {
      $fileExt = strtolower(end($fileExt));
    } else {
      $fileExt = null;
    }


    $tmpDownloadDir = "/tmp/telegram_tmp_download";
    $tmpFile = $tmpDownloadDir."/".bin2hex($telegramFileId).(
      isset($fileExt) ? ".".$fileExt : ""
    );


    /**
     * Make sure the target directory exists.
     */
    is_dir(STORAGE_PATH) or mkdir(STORAGE_PATH);
    is_dir(STORAGE_PATH."/telegram") or mkdir(STORAGE_PATH."/telegram");
    is_dir(STORAGE_PATH."/telegram/files") or mkdir(STORAGE_PATH."/telegram/files");
    is_dir($tmpDownloadDir) or mkdir($tmpDownloadDir);


    /**
     * Download the file.
     */
    $response = SaberGM::download(
      "https://api.telegram.org/file/bot".BOT_TOKEN."/".$v["result"]["file_path"],
      $tmpFile
    );


    /**
     * Download failed.
     */
    if (!file_exists($tmpFile)) {
      return null;
    }


    $md5Hash    = md5_file($tmpFile, true);
    $sha1Hash   = sha1_file($tmpFile, true);
    $targetFile = bin2hex($md5Hash).bin2hex($sha1_file).(
      isset($fileExt) ? ".".$fileExt : ""
    );

    rename($tmpFile, $targetFile);

    return $fileId;
  }
}
