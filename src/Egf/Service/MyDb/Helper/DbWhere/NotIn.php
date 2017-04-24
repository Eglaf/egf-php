<?php

namespace Egf\Base\Core\DbWhere;

/**
 * Class NotIn
 * Similar to In... but not.
 */
class NotIn extends In {

	/**
	 * A basic condition equation string. It uses NULL-safe equal operator.
	 * @return string
	 */
	public function getConditionEquation() {
		return ' NOT IN ';
	}

}