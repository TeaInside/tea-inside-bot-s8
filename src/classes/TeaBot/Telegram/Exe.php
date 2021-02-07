<?php

namespace TeaBot\Telegram {

use Exception;

final class Exe
{
	/**
	 * @const int
	 */
	public const MAX_TRY_COUNT = 5;

	/**
	 * @param string $method
	 * @param array  $args
	 * @return mixed
	 */
	public static function __callStatic(string $method, array $args)
	{
		return self::execPost($method, $args[0] ?? []);
	}


	/**
	 * @param string $path
	 * @param array  $body
	 * @return mixed
	 */
	private static function saberExec(string $path, array $body)
	{
		$saber = Saber::create([
			"base_uri" => "https://api.telegram.org",
			"headers"  => ["Content-Type" => ContentType::JSON],
			"timeout"  => 120,
		]);
		return $saber->post("/bot".TG_BOT_TOKEN."/".$path, $body);
	}


	/**
	 * @param string $path
	 * @param array  $body
	 * @return mixed
	 */
	public static function execPost(string $path, array $body)
	{
		$tryCounter = 0;
	again:
		try {
			return self::saberExec($path, $body);
		} catch (Exception $e) {

			if (++$tryCounter < self::MAX_TRY_COUNT) {
				/* dbg(p2) */
				pr_error("%s: %s: %s", __METHOD__,
					 get_class($e), $e->getMessage());
				/* end_dbg */
				goto again;
			}

			throw $e;
		}

		/* Unreachable */
		return NULL;
	}
}

} /* namespace TeaBot\Telegram */
