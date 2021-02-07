<?php

$dir = __DIR__;

require __DIR__."/src/init/global.php";
require BASEPATH."/src/helpers/global.php";
require BASEPATH."/src/helpers/build.php";

if (trim(shell_exec("whoami")) !== "root") {
	echo "Must be run as root!\n";
	exit(1);
}


/* Build swoole */
$swooleDir  = "{$dir}/phpext/swoole";
$swooleLock = "{$swooleDir}/build.lock";
if (!file_exists($swooleLock)) {
	shchdir($swooleDir);
	sh(
		"phpize && ".
		"./configure && ".
		"sed -i 's/-g -O0/-g -O3 -fno-omit-frame-pointer/g' Makefile && ".
		"make -j\$(nproc) && ".
		"make install && ".
		"touch ".escapeshellarg($swooleLock)
	);
	if (!file_exists($swooleLock)) {
		printf("\nFailed to build swoole!\n");
		exit(1);
	}
} else {
	printf("Swoole lock build file is detected, skipping swoole build...\n");
}


/* Telegram webhook directory */
$tgWebhookDir = PUBLIC_DIR."/".TG_WEBHOOK_KEY;
if (!is_dir($tgWebhookDir)) {
	if (!mkdir($tgWebhookDir)) {
		printf("Cannot create tgWebhookDir: %s\n", $tgWebhookDir);
		exit(1);
	}
	printf("Created tgWebhookDir: %s\n", $tgWebhookDir);
} else {
	printf("tgWebhookDir exists, skipping creation...\n");
}



exit(0);
