<?php

$__sh_cwd = __DIR__;

/**
 * @param string $dir
 * @return void
 */
function shchdir(string $dir): void
{
	global $__sh_cwd;
	$__sh_cwd = escapeshellarg($dir);
}


/**
 * @param string $dir
 * @return string|false
 */
function sh(string $cmd)
{
	global $__sh_cwd;
	return system("cd {$__sh_cwd} && sh -c ".escapeshellarg($cmd)." 2>&1");
}
