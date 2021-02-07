<?php

namespace DB {

use PDO;

abstract class DBFoundation
{
	/**
	 * @param array $param
	 * @return \PDO
	 */
	protected function constructPdo(array $param): PDO
	{
		return new PDO(...$param);
	}

	/**
	 * @return \PDO
	 */
	abstract public function createPDO(): PDO;
}

} /* namespace DB */
