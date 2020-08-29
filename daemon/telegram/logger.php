<?php

// \TeaBot\Telegram\Log::registerLogStream(STDOUT);
// \TeaBot\Telegram\Log::registerLogStream(fopen(TELEGRAM_DAEMON_LOG_FILE, "a"));

cli_set_process_title("logger");

return function () {

  $addr  = "tcp://127.0.0.1:7771";
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

  echo "logger_handler: Listening on {$addr}...\n";

  while ($conn = stream_socket_accept($sock, -1)) {

    /* Create a new routine every time we accepts new connection. */
    go(function () use ($conn) {
      logger_handler($conn);
    });

  }

  fclose($conn);
};

/** 
 * @param  $conn sock_fd
 * @return void
 */
function logger_handler($conn): void
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

  echo json_encode($data)."\n";

  // try {

  //   /* Run the bot logger. */
  //   $bot = new \TeaBot\Telegram\Logger($data);
  //   $bot->run();

  // } catch (\Error $e) {
  //   echo $e."\n";
  // }
}
