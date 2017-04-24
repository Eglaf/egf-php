<?php

namespace Egf\Service\MyDb\Helper\DbWhere;

/**
 * Abstract DbCondition Class
 */
abstract class Base {

    /** @var string The enabled type. it can be: 'i', 'd', 's', 'b', 'is', 'id', 'sb', etc... */
    protected $sType = 's';

    /** @var mixed The searched value. */
    protected $xValue = null;

	/**
	 * The condition string.
	 * @abstract
	 * @return string
	 */
	abstract public function getConditionEquation();

	/**
	 * DbWhere\Base constructor. Set value.
	 * @param mixed $xValue
	 */
	public function __construct($xValue) {
		$this->xValue = $xValue;
	}

    /**
     * Get type.
     * @return string
     */
    public function getType() {
        return $this->sType;
    }

    /**
     * Get value.
     * @return mixed
     */
    public function getValue() {
        return $this->xValue;
    }

	/**
	 * It gives back the prepare question mark, or more of them if the parameter is an array.
	 * @return string
	 */
    public function getOneOrMoreQuestionMark() {
    	if (is_array($this->xValue)) {
		    $aQuestionMarks = [];
		    foreach ($this->xValue as $xVal) {
			    $aQuestionMarks[] = '?';
		    }

		    return '(' . implode(',', $aQuestionMarks) . ')';
	    } else {
	    	return '?';
	    }
    }

}