<?php

require __DIR__."/../bootstrap/telegram/autoload.php";

error_reporting(E_ALL);
ini_set("display_errors", true);

/* Load configurations. */
loadConfig("telegram/api");
loadConfig("telegram/quran");
loadConfig("telegram/calculus");
loadConfig("telegram/telegram_bot");

/* Save PID. */
file_put_contents(TELEGRAM_DAEMON_PID_FILE, getmypid());

// \TeaBot\Telegram\Log::registerLogStream(STDOUT);
// \TeaBot\Telegram\Log::registerLogStream(fopen(TELEGRAM_DAEMON_LOG_FILE, "a"));

$fx = function () {

  $addr  = "tcp://127.0.0.1:7777";
  $ctx   = stream_context_create(
    [
      "socket" => [
        "so_reuseaddr" => true,
        "backlog" => 128
      ]
    ]
  );
  $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;

  /* Run coroutine server. */
  if (!($sock = stream_socket_server($addr, $errno, $errstr, $flags, $ctx))) {
    echo "$errstr ($errno)\n";
    return;
  }

  echo "Listening on {$addr}...\n";

  while ($conn = stream_socket_accept($sock, -1)) {

    /* Create a new routine every time we accepts new connection. */
    go(function () use ($conn) {
      client_handler($conn);
    });

  }

  fclose($conn);
};

\Swoole\Runtime::enableCoroutine();
\Co\run(function () use ($fx) { go($fx); });

/** 
 * @param  $conn sock_fd
 * @return void
 */
function client_handler($conn): void
{
  stream_set_timeout($conn, 10);

  $data        = fread($conn, 4096);
  $dataLen     = unpack("S", substr($data, 0, 2))[1];
  $receivedLen = strlen($data) - 2;
  $data        = substr($data, 2);

  /* Read more if the payload has not been received completely. */
  while ($dataLen > $receivedLen) {
    $data        .= fread($conn, 4096);
    $receivedLen  = strlen($data);
  }

  /* The payload must be a valid JSON array/object. */
  $data = json_decode($data, true);

  if (!is_array($data)) {

    /* Invalid payload. */
    fwrite($conn, "fail");
    fclose($conn);
    return;

  }

  fwrite($conn, "ok");
  fclose($conn);

  /* Send payload to old daemon. */
  go(function () use ($data) {
    $saber = \Swlib\Saber::create(
      [
        "base_uri" => "https://telegram-bot.teainside.org",
        "headers"  => ["Content-Type" => \Swlib\Http\ContentType::JSON],
        "timeout"  => 500,
      ]
    )->post("/webhook.php", $data);
  });


  try {

    /* Run the bot handler. */
    $bot = new \TeaBot\Telegram\TeaBot($data);
    $bot->run();

  } catch (\Error $e) {
    echo $e."\n";
  }
}
