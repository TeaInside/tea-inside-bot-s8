<?php

declare(strict_types=1);

/*
	[-- Standard Flags --]

	p1 = print action v
	p2 = print action vv
	p3 = print action vvv
	p4 = print action vvvv
	p5 = print action vvvvv
	warning = warning
	assert  = assertion
	global  = should only be turned off if all debug codes are off
*/

require __DIR__."/src/init/global.php";

$targetDir = [
	BASEPATH."/src"
];


/**
 * @param string $file
 * @param string $flag
 * @return int
 */
function turn_on_flag(string $file, string $flag): int
{
	$ret = 0;
	$flag = preg_quote($flag);
	$pat = "/\/\*\s*__dbg_code\(\s*({$flag})\s*\):b64:gz:(\S+?)\s*\*\//isS";

	$r1 = $r2 = [];
	$content = file_get_contents($file);
	if (preg_match_all($pat, $content, $m)) {
		printf("Turning on %d dbg flags in %s...", count($m), $file);
		foreach ($m[0] as $k => $v) {
			$code = gzdecode(base64_decode($m[2][$k]));
			$r1[] = $v;
			$r2[] = "/* dbg({$m[1][$k]}) */".$code."/* end_dbg */";
			$ret++;
		}
		file_put_contents($file, str_replace($r1, $r2, $content));
		printf("OK!\n");
	}

	return $ret;
}


/**
 * @param string $file
 * @param string $flag
 * @return int
 */
function turn_off_flag(string $file, string $flag): int
{
	$ret = 0;
	$flag = preg_quote($flag);
	$start = "\/\*\s*dbg\(\s*({$flag})\s*\)\s*\*\/";
	$end = "\/\*\s*end_dbg\s*\*\/";
	$pat = "/{$start}(.*?){$end}/isS";


	$r1 = $r2 = [];
	$content = file_get_contents($file);
	if (preg_match_all($pat, $content, $m)) {
		printf("Turning off %d dbg flags in %s...", count($m), $file);
		foreach ($m[0] as $k => $v) {
			$comp = base64_encode(gzencode($m[2][$k], 9));
			$r1[] = $v;
			$r2[] = "/* __dbg_code({$m[1][$k]}):b64:gz:{$comp} */";
			$ret++;
		}
		file_put_contents($file, str_replace($r1, $r2, $content));
		printf("OK!\n");
	}

	return $ret;
}


/**
 * @param string $file
 * @param int    $bit
 * @param string $flag
 * @return void
 */
function toggle_debug_flag(string $file, int $bit, string $flag): int
{
	return $bit ? turn_on_flag($file, $flag) : turn_off_flag($file, $flag);
}


/**
 * @param string $dir
 * @param int    $bit
 * @param string $flag
 * @return void
 */
function scan_callback(string $dir, int $bit, string $flag): void
{
	$scan = scandir($dir);
	foreach ($scan as $k => $v) {
		if ($v === "." || $v === "..")
			continue;

		$target = $dir."/".$v;

		if (is_dir($target)) {
			scan_callback($target, $bit, $flag);
			continue;
		}

		if (preg_match("/\\.php$/S", $v))
			toggle_debug_flag($target, $bit, $flag);
	}
}

/**
 * @param string $app
 * @return void
 */
function cli_usage(string $app): void
{
	printf("Usage: php %s [on|off] [flag]\n", $app);
}


if (defined("DBG_C_INCLUDE"))
	return;


if (PHP_SAPI != "cli") {
	printf("PHP_SAPI is not cli!\n");
	exit(1);
}


if ((!isset($argv[1], $argv[2])) || ($argc !== 3)) {
	cli_usage($argv[0]);
	exit(1);
}

$argv[1] = strtolower($argv[1]);
if ($argv[1] === "on" || $argv[1] === "off") {
	$argv[1] = ($argv[1][1] === "n") ? 1 : 0;
} else {
	printf("Invalid argv[1] = \"%s\"\n", $argv[1]);
	cli_usage($argv[0]);
	exit(1);
}


foreach ($targetDir as $k => $dir) {

	if (!is_dir($dir)) {
		printf("Invalid directory: %s\n", $dir);
		exit(1);
	}

	printf("Scanning %s...\n", $dir);
	scan_callback($dir, $argv[1], $argv[2]);
}

exit(0);
