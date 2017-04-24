<?php

namespace Egf\Base\Core\DbWhere;

/**
 * Class In
 */
class In extends Base {

	/**
	 * A basic condition equation string. It uses NULL-safe equal operator.
	 * @return string
	 */
	public function getConditionEquation() {
		return ' IN ';
	}

}