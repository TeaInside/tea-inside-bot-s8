<?php

cli_set_process_title("logger_worker_{$k}");

$GLOBALS["forwardBaseUrl"] = getenv("FORWARD_BASE_URL");
$GLOBALS["forwardPath"]    = getenv("FORWARD_PATH");

if (!$GLOBALS["forwardBaseUrl"]) {
  echo "Warning: FORWARD_BASE_URL is not provided!\n";
}

if (!$GLOBALS["forwardPath"]) {
  echo "Warning: FORWARD_PATH is not provided!\n";
}

$tcpAddr = "tcp://{$bindAddr}";
unset($bindAddr);
$ctx  = stream_context_create(["socket" => ["so_reuseaddr" => false, "backlog" => 500]]);
$sock = stream_socket_server($tcpAddr, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $ctx);

if (!$sock) {
  echo "({$errno}): {$errstr}\n";
  return;
}
unset($errno, $errstr, $ctx);

echo "logger_worker_{$k} is listening on {$tcpAddr}...\n";

/* Accepting client... */
while ($conn = stream_socket_accept($sock, -1)) {
  /* Create a new coroutine every time we accept new connection. */
  go(function () use ($conn, $k) { logger_handler($conn, $k); });
}


/** 
 * @param  sock_fd $conn
 * @param  int     $k
 * @return void
 */
function logger_handler($conn, int $k): void
{
  global $forwardBaseUrl, $forwardPath, $workers;

  echo "logger_worker_{$k} is accepting connection...\n";

  stream_set_timeout($conn, 10);

  $rawData      = fread($conn, 4096);
  $dataLen      = unpack("S", substr($rawData, 0, 2))[1];
  $receivedLen  = strlen($rawData) - 2;
  $rawData      = substr($rawData, 2);

  /* Read more if the payload has not been received completely. */
  while ($dataLen > $receivedLen) {
    $rawData     .= fread($conn, 4096);
    $receivedLen  = strlen($rawData);
  }

  /* The payload must be a valid JSON array/object. */
  $data = json_decode($rawData, true);

  if (!is_array($data)) {

    /* Invalid payload. */
    fwrite($conn, "fail");
    fclose($conn);
    return;

  }

  fwrite($conn, "ok");
  fclose($conn);

  /* Send payload to the old daemon. */
  if ($forwardBaseUrl && $forwardPath) {
    go(function () use ($data, $forwardBaseUrl, $forwardPath) {
      payload_forwarder($data, $forwardBaseUrl, $forwardPath);
    });
  }

  try {
    $logger = new \TeaBot\Telegram\Logger($data);
    $logger->run();
  } catch (\Error $e) {
    echo "{$e}\n";
    telegram_daemon_error_report($e, json_encode($data, JSON_UNESCAPED_SLASHES));
  }
}
