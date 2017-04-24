<?php

namespace Egf\Base\Core\DbWhere;

/**
 * Class Greater
 */
class Greater extends Base {
	
	/** @var string Type. */
	protected $sType = 's';
	
	/**
	 * A basic condition greater string.
	 * @return string
	 */
	public function getConditionEquation() {
		return ' > ';
	}
	
	
}