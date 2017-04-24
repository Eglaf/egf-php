<?php

namespace Egf\Base\Core\DbWhere;

/**
 * Class Equal
 */
class Equal extends Base {

	/** @var string Type. */
	protected $sType = 's';

	/**
	 * A basic condition equation string. It uses NULL-safe equal operator.
	 * @return string
	 */
	public function getConditionEquation() {
		return ' <=> ';
	}

}