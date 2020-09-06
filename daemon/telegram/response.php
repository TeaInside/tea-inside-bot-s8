<?php

const FLAGS = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;

$bindAddr       = getenv("BIND_ADDR");
$bindPort       = getenv("BIND_PORT");
$forwardBaseUrl = getenv("FORWARD_BASE_URL");
$forwardPath    = getenv("FORWARD_PATH");

if (!$bindAddr) {
  $bindAddr = "127.0.0.1";
}

if (!$bindPort) {
  $bindPort = 7777;
}

if (!$forwardBaseUrl) {
  echo "Warning: FORWARD_BASE_URL is not provided!\n";
}

if (!$forwardPath) {
  echo "Warning: FORWARD_PATH is not provided!\n";
}

$tcpAddr = "tcp://{$bindAddr}:{$bindPort}";

$ctx = stream_context_create(
  [
    "socket" => [
      "so_reuseaddr" => true,
      "backlog"      => 128
    ]
  ]
);

if (!($sock = stream_socket_server($tcpAddr, $errno, $errstr, FLAGS, $ctx))) {
  echo "({$errno}): {$errstr}\n";
  return;
}

echo "response_handler is running...\n";
echo "Listening on {$tcpAddr}...\n";

/* Accepting client... */
while ($conn = stream_socket_accept($sock, -1)) {
  /* Create a new coroutine every time we accept new connection. */
  go(function () use ($conn) { client_handler($conn); });
}


/** 
 * @param  $conn sock_fd
 * @return void
 */
function response_handler($conn): void
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

  /* Send payload to the old daemon. */
  if ($forwardBaseUrl && $forwardPath) {
    go(function () use ($data, $forwardBaseUrl, $forwardPath) {
      payload_forwarder($data, $forwardBaseUrl, $forwardPath);
    });
  }

  try {

    /* Run the bot handler. */
    $bot = new \TeaBot\Telegram\TeaBot($data);
    $bot->run();

  } catch (\Error $e) {
    echo "{$e}\n";
  }
}
