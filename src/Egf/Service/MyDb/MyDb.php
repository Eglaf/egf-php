<?php

namespace Egf\Service\MyDb;

use Egf\Util;
use Egf\Service\MyDb\Helper\Connection;

/**
 * Class MyDb
 */
class MyDb extends \Egf\Ancient\Service {

	/** @var array $aInitiativeConnections Connection details. */
	protected $aInitiativeConnections = [];

	/** @var Connection[] $aConnections Real connections to a db. */
	protected $aConnections = [];

	/** @var string Name of the default connection. */
	protected $sDefaultConnection = '';


	/**
	 * Init.
	 */
	protected function init() {
		if ( ! $this->get('permCache')->has('egf/mydb/connections')) {
			$xMyDbDetails           = $this->getParam('myDb');
			$aInitiativeConnections = [];

			// Multiple db connections.
			if (Util::isArraySequential($xMyDbDetails)) {
				foreach ($xMyDbDetails as $aOneMyDbDetails) {
					if ($this->isValidConnection($aOneMyDbDetails)) {
						if (isset($aOneMyDbDetails['default']) && $aOneMyDbDetails['default'] == TRUE) {
							$this->sDefaultConnection = $aOneMyDbDetails['name'];
						}
						$aInitiativeConnections[ $aOneMyDbDetails['name'] ] = $aOneMyDbDetails;
					}
					else {
						throw $this->get('log')->exception('Invalid MyDb connection!');
					}
				}
			}
			// Single db connection.
			else {
				// Single connection does not need a name, but it's required.
				if ( ! isset($xMyDbDetails['name'])) {
					$xMyDbDetails['name'] = 'default';
				}

				if ($this->isValidConnection($xMyDbDetails)) {
					$xMyDbDetails['default']                         = TRUE;
					$aInitiativeConnections[ $xMyDbDetails['name'] ] = $xMyDbDetails;
				}
				else {
					throw $this->get('log')->exception('Invalid MyDb connection!');
				}
			}

			$this->get('permCache')->set('egf/mydb/connections', $aInitiativeConnections);
		}

		$this->aInitiativeConnections = $this->get('permCache')->get('egf/mydb/connections');

	}


	/**
	 * Calling method of the default connection.
	 * @param string $sMethod    Method of the connection.
	 * @param array  $aArguments Arguments of the connection.
	 * @return mixed
	 */
	public function __call($sMethod, $aArguments) {
		if ($this->sDefaultConnection) {
			return Util::callObjectMethod($this->getConnection($this->sDefaultConnection), $sMethod, $aArguments);
		}
		else {
			throw $this->get('log')->exception('No default connection set!');
		}
	}

	/**
	 * Get a MyDb connection.
	 * @param string $sConnectionName
	 * @return Connection
	 */
	public function getConnection($sConnectionName) {
		if ( ! isset($this->aConnections[ $sConnectionName ])) {
			if (isset($this->aInitiativeConnections[ $sConnectionName ])) {
				$this->aConnections[ $sConnectionName ] = new Helper\Connection($this->app, $this->aInitiativeConnections[ $sConnectionName ]);
			}
		}

		return $this->aConnections[ $sConnectionName ];
	}

	/**
	 * It gives back comma separated question marks between brackets.
	 * @param array $aParams   Parameters.
	 * @param bool  $bBrackets Decide if the brackets should be there too. Default: True.
	 * @return string Question marks (Number of parameters).
	 */
	public function arrayAsQuestionMarks(array $aParams, $bBrackets = TRUE) {
		$sResult = trim(str_repeat('?, ', count($aParams)), ', ');

		return ($bBrackets ? (' (' . $sResult . ') ') : $sResult);
	}


	/**
	 * Check if the myDb details are valid.
	 * @param array $aMyDb
	 * @return bool
	 */
	protected function isValidConnection($aMyDb) {
		return (is_array($aMyDb) && isset($aMyDb['name']) && isset($aMyDb['host']) && isset($aMyDb['username']) && isset($aMyDb['password']) && isset($aMyDb['database']));
	}


}
