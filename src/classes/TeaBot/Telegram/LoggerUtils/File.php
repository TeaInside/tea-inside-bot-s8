<?php

namespace TeaBot\Telegram\LoggerUtils;

use DB;
use PDO;
use Error;
use Exception;
use Swlib\SaberGM;
use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Dlog;
use TeaBot\Telegram\Mutex;
use TeaBot\Telegram\LoggerUtilFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\LoggerUtils
 * @version 8.0.0
 */
class File extends LoggerUtilFoundation
{
  /**
   * @param string $tgFileId
   * @return int
   */
  public function resolveFile(string $tgFileId): ?int
  {
    $fileId  = null;   
    $e       = null;
    $uniqId  = md5($tgFileId);
    $mutex   = new Mutex("tg_files", "{$uniqId}");
    $mutex->lock();

    $pdo = $this->pdo;
    $st  = $pdo->prepare("SELECT id FROM tg_files WHERE tg_file_id = ? LIMIT 1");
    $st->execute([$tgFileId]);

    if ($u = $st->fetch(PDO::FETCH_NUM)) {
      /* File has already been stored in database. */
      $fileId = (int)$u[0];
      goto ret;
    }

    if ($u = self::downloadFile($tgFileId, $uniqId)) {
      $j = $u["j"];
      $pdo
        ->prepare("INSERT INTO tg_files (tg_file_id, tg_uniq_id, md5_sum, sha1_sum, file_type, ext, size, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute(
          [
            $j["file_id"],
            $j["file_unique_id"],
            hex2bin($u["md5"]),
            hex2bin($u["sha1"]),
            mime_content_type($u["fix_file"]),
            $u["ext"],
            $j["file_size"],
            date("Y-m-d H:i:s")
          ]
        );
      $fileId = (int)$pdo->lastInsertId();
    } else {
      /* Cannot download the file. */
    }

    ret:
    $mutex->unlock();
    return $fileId;
  }


  /**
   * @param string $tgFileId
   * @param string $uniqId
   * @return ?array
   */
  private static function downloadFile(string $tgFileId, string $uniqId): ?array
  {
    $retVal = null;
    $ret    = Exe::getFile(["file_id" => $tgFileId]);
    $j      = json_decode($ret->getBody()->__toString(), true);


    /* debug:warning */
    $__json_warning = json_encode(["tg_file_id" => $tgFileId]);
    /* end_debug */


    if (!isset($j["result"])) {
      /* debug:warning */
      Dlog::warning("getFile failed, missing field \"result\": %s", $__json_warning);
      /* end_debug */
      goto ret;
    }

    $j = $j["result"];

    if (!isset($j["file_id"], $j["file_unique_id"], $j["file_size"], $j["file_path"])) {
      /* debug:warning */
      Dlog::warning(
        "getFile failed, missing fields: %s",
        json_encode(["tg_file_id" => $tgFileId, "cur_fields" => array_keys($j)])
      );
      /* end_debug */
      goto ret;
    }

    /* debug:warning */
    $__json_warning = json_encode($j);
    /* end_debug */

    $fileDir = TELEGRAM_STORAGE_PATH."/files";
    $tmpDir  = TELEGRAM_STORAGE_PATH."/tmp_download";
    $ext     = explode(".", $j["file_path"]);
    $c       = count($ext);

    is_dir(TELEGRAM_STORAGE_PATH) or mkdir(TELEGRAM_STORAGE_PATH);
    is_dir($fileDir) or mkdir($fileDir);
    is_dir($tmpDir) or mkdir($tmpDir);

    if ($c < 2) {
      $ext = null;
    } else {
      $ext = $ext[$c - 1];
    }

    $tmpFile  = "{$tmpDir}/{$uniqId}".(is_null($ext) ? "" : ".{$ext}");
    $downTry  = 0;
    download:

    try {
      /* debug:p4 */
      Dlog::out("Downloading file...: %s", $j["file_id"]);
      /* end_debug */
      $downTry++;
      $response = SaberGM::download(
        "https://api.telegram.org/file/bot".BOT_TOKEN."/{$j["file_path"]}",
        $tmpFile,
        0,
        ["timeout" => 600]
      );
    } catch (Exception $e) {
      /* debug:warning */
      Dlog::warning("SaberGM::download file failed: %s", $e->getMessage());
      /* end_debug */
      goto retry_download;
    }

    if (!$response->getSuccess()) {
      /* debug:warning */
      Dlog::warning("SaberGM::download file failed: no expcetion");
      /* end_debug */
      goto retry_download;
    }

    $fileSize = filesize($tmpFile);
    if ($fileSize < $j["file_size"]) {
      /* debug:warning */
      Dlog::warning(
        "Downloaded size is less than file size info, may be corrupted: %s",
        $__json_warning
      );
      /* end_debug */
      goto retry_download;
    }

    $md5Hash  = md5_file($tmpFile);
    $sha1Hash = sha1_file($tmpFile);
    $fullHash = $md5Hash.$sha1Hash;

    $indexDir = self::genIndexDir($fileDir, substr($fullHash, 0, 14));
    $fixFile  = "{$indexDir}/{$fullHash}".(is_null($ext) ? "" : ".{$ext}");

    /* debug:p4 */
    Dlog::out("Download success, (%s) stored at: %s", $j["file_id"], $tmpFile);
    Dlog::out("Full hash file (%s): %s", $j["file_id"], $fullHash);
    /* end_debug */

    $retVal = [
      "fix_file"  => $fixFile,
      "md5"       => $md5Hash,
      "sha1"      => $sha1Hash,
      "ext"       => $ext,
      "j"         => $j,
    ];

    if (rename($tmpFile, $fixFile)) {
      Dlog::out("Finished, fix file: (%s) %s", $j["file_id"], $fixFile);
    } else {
      $fixFileRecover = "{$tmpDir}/{$fullHash}".(is_null($ext) ? "" : ".{$ext}");

      /* debug:warning */
      Dlog::warning("Cannot move file to %s", $fixFile);
      /* end_debug */

      if (rename($tmpFile, $fixFileRecover)) {
        /* debug:warning */
        Dlog::warning("Renamed %s to %s", $tmpFile, $fixFileRecover);
        /* end_debug */
      } else {

        /* debug:warning */
        Dlog::warning(
          "Cannot recovering file at all, return null: (fullhash: %s) %s",
          $fullHash,
          $tmpFile
        );
        /* end_debug */

        $retVal = null;
      }
    }


    ret:
    return $retVal;

    retry_download:
    if ($downTry > 5) {
      /* debug:warning */
      Dlog::warning("Cannot recover fail download (rcount: %d), return null", $downTry);
      /* end_debug */
      return null;
    }
    /* debug:warning */
    Dlog::warning("Retrying download (rcount: %s): %s", $downTry, $__json_warning);
    /* end_debug */
    goto download;
  }


  /**
   * @param string $fileDir
   * @param string $hash
   * @return void
   */
  private static function genIndexDir(string $fileDir, string $hash): string
  {
    $hash = str_split($hash, 2);
    $tdir = $fileDir;
    foreach ($hash as $c) {
      $tdir .= "/{$c}";
      is_dir($tdir) or mkdir($tdir);
    }
    return $tdir;
  }
}
