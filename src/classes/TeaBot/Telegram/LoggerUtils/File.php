<?php

namespace TeaBot\Telegram\LoggerUtils;

use DB;
use PDO;
use Exception;
use Swlib\SaberGM;
use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Mutex;
use TeaBot\Telegram\LoggerUtilFoundation;
use Swlib\Http\Exception\ConnectException;

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
  public function resolveFile(string $tgFileId): int
  {
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


    $hashes = self::downloadFile($tgFileId, $uniqId);



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

    if (!isset($j["result"])) {
      /*debug:2*/
      echo __FILE__.":".__LINE__.": Missing field \"result\", return null\n";
      /*endddebug*/
      goto ret;
    }

    $j = $j["result"];

    if (!isset($j["file_id"], $j["file_unique_id"], $j["file_size"], $j["file_path"])) {
      /*debug:2*/
      echo __FILE__.":".__LINE__.": Missing field some fields, return null, curfields: "
        .json_encode(array_keys($j))."\n";
      /*endddebug*/
      goto ret;
    }


    $fileDir = TELEGRAM_STORAGE_PATH."/files";
    $tmpDir  = TELEGRAM_STORAGE_PATH."/tmp_download";

    is_dir(TELEGRAM_STORAGE_PATH) or mkdir(TELEGRAM_STORAGE_PATH);
    is_dir($fileDir) or mkdir($fileDir);
    is_dir($tmpDir) or mkdir($tmpDir);

    $ext      = explode(".", $j["file_path"]);
    $c        = count($ext);

    if ($c < 2) {
      $ext = null;
    } else {
      $ext = $ext[$c - 1];
    }

    $tmpFile  = "{$tmpDir}/{$uniqId}".(is_null($ext) ? "" : ".{$ext}");
    $downTry  = 0;

    download:
    $downTry++;
    try {
      $response = SaberGM::download(
        "https://api.telegram.org/file/bot".BOT_TOKEN."/{$j["file_path"]}",
        $tmpFile,
        0,
        ["timeout" => 500]
      ); 
    } catch (ConnectException $e) {
      /*debug:2*/
      echo "Download failed: {$e->getMessage()}!\n";
      /*enddebug*/
      goto retry_download;
    }

    if (!$response->getSuccess()) {
      goto retry_download;
    }

    $fileSize = filesize($tmpFile);

    if ($fileSize < $j["file_size"]) {
      /* (downloaded file is corrupted). */
      goto retry_download;
    }

    $md5Hash  = md5_file($tmpFile);
    $sha1Hash = sha1_file($tmpFile);
    $fullHash = $md5Hash.$sha1Hash;

    $indexDir = self::genIndexDir($fileDir, substr($fullHash, 0, 14));
    $fixFile  = "{$indexDir}/{$fullHash}".(is_null($ext) ? "" : ".{$ext}");

    rename($tmpFile, $fixFile);

    ret:
    return $retVal;

    retry_download:
    if ($downTry > 5) {
      /*debug:2*/
      echo "Cannot recover fail download, return null\n";
      return null;
      /*enddebug*/
    }
    /*debug:2*/
    echo "Retrying...\n";
    /*enddebug*/
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
