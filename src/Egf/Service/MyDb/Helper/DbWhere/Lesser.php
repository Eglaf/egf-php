<?php

namespace Egf\Base\Core\DbWhere;

/**
 * Class Lesser
 */
class Lesser extends Base {
	
	/** @var string Type. */
	protected $sType = 's';
	
	/**
	 * A basic condition lesser string.
	 * @return string
	 */
	public function getConditionEquation() {
		return ' < ';
	}
	
	
}