<?php

namespace Egf\Service\MyDb\Helper;

use Egf\App;

/**
 * Class Connection
 * MyDb Connection.
 */
class Connection {

	/** @var App $app */
	protected $app;

	/** @var \mysqli $connection */
	protected $connection;

	/** @var int Generated ID of the last inserted row. */
	protected $iLastInsertId = 0;

	/** @var int Number of affected rows. */
	protected $iAffectedRows = 0;


	/**
	 * Connection constructor.
	 * @param App   $app
	 * @param array $aMyDbDetails
	 */
	public function __construct(App $app, array $aMyDbDetails) {
		$this->app        = $app;
		$this->connection = new \mysqli($aMyDbDetails['host'], $aMyDbDetails['username'], $aMyDbDetails['password'], $aMyDbDetails['database']);

		if (mysqli_connect_error()) {
			throw $this->app->get('log')->exception('Failed to connect to MySql! ' . mysqli_connect_error());
		}

		// Charset.
		if (isset($aMyDbDetails['charset'])) {
			mysqli_set_charset($this->connection, $aMyDbDetails['charset']);
		}
		else {
			mysqli_set_charset($this->connection, 'utf8');
		}
	}


	/**
	 * Run query.
	 * @param string     $sQuery  The Sql query.
	 * @param array|NULL $aParams Associative array of optional parameters. Array['type'] can be one ore more of these: i, s, d, b. Array['value'] is the searched value.
	 * @return array|\mysqli_result|bool
	 */
	public function query($sQuery, array $aParams = []) {
		/** @var \mysqli_stmt $stmt */
		$stmt = $this->connection->prepare($sQuery);
		if ($stmt) {
			if (is_array($aParams) && count($aParams)) {
				$aValues = [0 => ''];
				foreach ($aParams as $xParam) {
					if ($xParam instanceof DbWhere\Base) {
						if (is_array($xParam->getValue())) {
							foreach ($xParam->getValue() as $xVal) {
								$aValues[0] .= $xParam->getType();
								$aValues[] = $xVal;
							}
						}
						else {
							$aValues[0] .= $xParam->getType();
							$aValues[] = $xParam->getValue();
						}
					}
					elseif (is_array($xParam) && isset($xParam['type']) && (isset($xParam['value']) || is_null($xParam['value']))) {
						$aValues[0] .= $xParam['type'];
						$aValues[] = $xParam['value'];
					}
					else {
						$aValues[0] .= 's';
						$aValues[] = $xParam;
					}
				}

				// Bind parameters to statement.
				call_user_func_array([$stmt, 'bind_param'], $this->referenceValues($aValues));

			}
			$stmt->execute();
			$xResult = $stmt->get_result();

			$this->iLastInsertId = $this->connection->insert_id;
			$this->iAffectedRows = $this->connection->affected_rows;

			$stmt->close();

			return $xResult;
		}
		else {
			throw $this->app->get('log')->exception("Invalid Sql query! {$this->app->get('log')->nl()} {$sQuery} {$this->app->get('log')->nl()}" . var_export($aParams, TRUE));
		}
	}

	/**
	 * Gives back the last inserted id.
	 * @return int
	 */
	public function getLastInsertId() {
		return $this->iLastInsertId;
	}

	/**
	 * It gives back the number of affected rows.
	 * @return int
	 */
	public function getAffectedRows() {
		return $this->iAffectedRows;
	}

	/**
	 * Escape a string.
	 * @param mixed $xVar Unsecured string.
	 * @return string Secured string.
	 */
	public function escape($xVar) {
		return $this->connection->real_escape_string($xVar);
	}


	/**
	 * Transform parameter values into reference.
	 * @param array $arr
	 * @return array
	 */
	protected function referenceValues($arr) {
		// Reference is required for PHP 5.3+
		if (strnatcmp(phpversion(), '5.3') >= 0) {
			$refs = [];
			foreach ($arr as $key => $value) {
				$refs[ $key ] = &$arr[ $key ];
			}

			return $refs;
		}

		return $arr;
	}

}