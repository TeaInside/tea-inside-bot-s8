<?php

$dir = __DIR__;

require "{$dir}/src/helpers/build.php";

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
} else {
	echo "Swoole lock build file is detected, skipping swoole build...\n";
}
