<?php

$GLOBALS["nlogger"]    = count($GLOBALS["loggersPid"]);
$GLOBALS["nresponder"] = count($GLOBALS["respondersPid"]);
$tcpAddr               = "tcp://".TELEGRAM_DAEMON_MASTER_BA;


$ctx  = stream_context_create(["socket" => ["so_reuseaddr" => true, "backlog" => 500]]);
$sock = @stream_socket_server($tcpAddr, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $ctx);


if (!$sock) {
  echo "({$errno}): {$errstr}\n";
  return;
}

unset($errno, $errstr, $ctx);

echo "master_worker is listening on {$tcpAddr}...\n";

$i = 0;

$GLOBALS["table"] = new \Swoole\Table(2);
$GLOBALS["table"]->column("i", \Swoole\Table::TYPE_INT);
$GLOBALS["table"]->create();
$GLOBALS["table"]["logger"] = ["i" => 0];
$GLOBALS["table"]["responder"] = ["i" => 0];

while ($conn = stream_socket_accept($sock, -1)) {
  go(function () use ($conn) { conn_handler($conn); });
}

/**
 * @param mixed $conn
 */
function conn_handler($conn)
{
  stream_set_timeout($conn, 5);
  $rawData      = fread($conn, 4096);
  $dataLen      = unpack("S", substr($rawData, 0, 2))[1];
  $receivedLen  = strlen($rawData);

  /* Read more if the payload has not been received completely. */
  while ($dataLen > $receivedLen) {
    $rawData     .= fread($conn, 4096);
    $receivedLen  = strlen($rawData);
  }

  // go(function () use ($conn, $rawData, $receivedLen) {
  //   send_to_responder($conn, $rawData, $receivedLen);
  // });
  go(function () use ($conn, $rawData, $receivedLen) {
    send_to_logger($conn, $rawData, $receivedLen);
  });

  fwrite($conn, "ok");
  fclose($conn);
}


/**
 * @param mixed $conn
 */
function send_to_responder($conn, $rawData, $receivedLen): void
{
  global $nresponder, $table;

  send_data:
  if (($i = $table["responder"]["i"]) >= $nresponder) {
    $i = 0;
  }
  $table["responder"]["i"] = $i + 1;

  $ct = TELEGRAM_DAEMON_RESPONDER_WORKERS[$i];
  $fp = stream_socket_client($ct, $errno, $errstr, 1);
  if (!$fp) {
    echo "Cannot init socket to {$ct}: ($errno): {$errstr}\n";
    echo "Recovering to another responder worker...\n";
    goto send_data;
  }

  fwrite($fp, $rawData);
  fclose($fp);
}

/**
 * @param mixed $conn
 * @return void
 */
function send_to_logger($conn, $rawData, $receivedLen): void
{
  global $nlogger, $table;

  send_data:
  if (($i = $table["logger"]["i"]) >= $nlogger) {
    $i = 0;
  }
  $table["logger"]["i"] = $i + 1;

  $ct = TELEGRAM_DAEMON_LOGGER_WORKERS[$i];
  $fp = stream_socket_client($ct, $errno, $errstr, 1);
  if (!$fp) {
    echo "Cannot init socket to {$ct}: ($errno): {$errstr}\n";
    echo "Recovering to another logger worker...\n";
    goto send_data;
  }

  fwrite($fp, $rawData);
  fclose($fp);
}
