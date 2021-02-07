<?php

namespace TeaBot\Telegram\LoggerFoundationTraits;

use DB;
use PDO;
use Swlib\SaberGM;
use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Exceptions\LoggerException;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\LoggerFoundationTraits
 * @version 8.0.0
 */
trait FileResolver
{
  /**
   * @param string $tgFileId
   * @param bool   $addHitCount
   * @return ?int
   */
  private static function baseFileResolve(string $tgFileId, bool $addHitCount = false)
  {
    /*
     * Check the $tgFileId in database.
     * If it has already been stored, then returns
     * the stored primary key. Otherwise, it
     * downloads the file and insert it to the
     * database.
     */

    $pdo = DB::pdo();
    $st  = $pdo->prepare("SELECT `id` FROM `tg_files` WHERE tg_file_id = ? FOR UPDATE");
    $st->execute([$tgFileId]);

    if ($r = $st->fetch(PDO::FETCH_NUM)) {

      if ($addHitCount) {
        $pdo->prepare("UPDATE `tg_files` SET `hit_count`=`hit_count`+1 WHERE `id`=?")
          ->execute([$r[0]]);
      }

      return (int)$r[0];
    }

    /* This operation may error. */
    $v = json_decode(
      Exe::getFile(["file_id" => $tgFileId])
      ->getBody()->__toString(),
      true
    );


    /* Return null if it cannot find the file path. */
    if (!isset($v["result"]["file_path"])) {
      return null;
    }


    /* Get file extension. */
    $fileExt = explode(".", $v["result"]["file_path"]);
    if (count($fileExt) > 1) {
      $fileExt = strtolower(end($fileExt));
    } else {
      $fileExt = null;
    }


    $tmpDownloadDir = "/tmp/telegram_tmp_download";
    $tmpFile = $tmpDownloadDir."/".bin2hex($tgFileId).(
      isset($fileExt) ? ".".$fileExt : ""
    );


    /* Make sure the target directory exists. */
    is_dir(STORAGE_PATH) or mkdir(STORAGE_PATH);
    is_dir(STORAGE_PATH."/telegram") or mkdir(STORAGE_PATH."/telegram");
    is_dir(STORAGE_PATH."/telegram/files") or mkdir(STORAGE_PATH."/telegram/files");
    is_dir($tmpDownloadDir) or mkdir($tmpDownloadDir);

    $tryCount = 0;
    retry_download:

    $tryCount++;
    /* Download the file. */
    $response = SaberGM::download(
      "https://api.telegram.org/file/bot".BOT_TOKEN."/".$v["result"]["file_path"],
      $tmpFile
    );


    /* Download failed. */
    if (!file_exists($tmpFile)) {
      if ($tryCount <= 5) goto retry_download;
      return null;
    }


    $md5Hash = md5_file($tmpFile, true);
    $sha1Hash = sha1_file($tmpFile, true);
    $fullHexHash = bin2hex($md5Hash).bin2hex($sha1Hash);
    $indexPath = self::genIndexPath($fullHexHash);
    $targetFile = STORAGE_PATH."/telegram/files/".$indexPath."/".$fullHexHash.(
      isset($fileExt) ? ".".$fileExt : ""
    );

    self::mkdirRecursive(STORAGE_PATH."/telegram/files/".$indexPath);

    @rename($tmpFile, $targetFile);
    @unlink($tmpFile);

    /* Move file failed. */
    if (!file_exists($targetFile)) {
      return null;
    }

    $fileSize = filesize($targetFile);
    if (!$fileSize) {
      if ($tryCount <= 5) goto retry_download;
      return null;
    }

    /* Check by hash file. */
    $st = $pdo->prepare("SELECT `id` FROM `tg_files` WHERE `md5_sum` = ? AND `sha1_sum` = ? LIMIT 1 FOR UPDATE");
    $st->execute([$md5Hash, $sha1Hash]);

    if ($u = $st->fetch(PDO::FETCH_NUM)) {
      $u = (int)$u[0];

      /*
       * This part handles duplicate file
       * with different telegram file id.
       * In this case, we update the
       * supplied tg_file_id.
       */

      $hitCountQuery = $addHitCount ? "`hit_count`=`hit_count`+1," : "";
      $pdo->prepare("UPDATE `tg_files` SET {$hitCountQuery} `tg_file_id` = ? WHERE `id` = ?")
      ->execute([$tgFileId, $u]);


      $fileId = $u;
    } else {

      $pdo->prepare("INSERT INTO `tg_files` (`tg_file_id`, `md5_sum`, `sha1_sum`, `file_type`, `ext`, `size`, `hit_count`, `description`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NOW())")
      ->execute(
        [
          $tgFileId,
          $md5Hash,
          $sha1Hash,
          mime_content_type($targetFile),
          $fileExt,
          $fileSize,
          $addHitCount ? 1 : 0
        ]
      );

      $fileId = $pdo->lastInsertId();
    }

    return $fileId;
  }

  /**
   * @param string $fullHexHash
   * @return string
   */
  public static function genIndexPath(string $fullHexHash): string
  {
    return implode("/", str_split(substr($fullHexHash, 0, 14), 2));
  }

  /**
   * @param string $dir
   * @return void
   */
  public static function mkdirRecursive(string $dir): void
  {
    $exp = explode("/", $dir);
    if (count($exp) == 1) return;

    $dir = "";
    foreach ($exp as $p) {
      $dir .= $p."/";
      is_dir($dir) or mkdir($dir, 0755);
    }
  }
}
