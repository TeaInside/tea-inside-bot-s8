<?php

$lock = new \Swoole\Lock(SWOOLE_FILELOCK, "php://memory");
echo "Aqcuring lock..\n";
$lock->lock();
echo "Locking...\n";
sleep(30);
$lock->unlock();
echo "Unlocked!\n";
