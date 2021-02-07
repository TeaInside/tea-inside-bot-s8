<?php

namespace DB {

use PDO;

class TgDb extends DBFoundation
{
	/**
	 * @return \PDO
	 */
	public static function createPDO(): \PDO
	{
		/* dbg(assert) */
		$assert = function ($msg) {
			printf("Error: %s: %s\n", __METHOD__, $msg);
			exit(1);
		};
		$assert("TG_BOT_DB_HOST");
		$assert("TG_BOT_DB_PORT");
		$assert("TG_BOT_DB_NAME");
		$assert("TG_BOT_DB_USER");
		$assert("TG_BOT_DB_PASS");
		/* end_dbg */
		return parent::constructPdo(
			"mysql:host=".TG_BOT_DB_HOST.";".
			"port=".TG_BOT_DB_PORT.";".
			"dbname=".TG_BOT_DB_NAME,
			TG_BOT_DB_USER,
			TG_BOT_DB_PASS
		);
	}
}

} /* namespace DB */
