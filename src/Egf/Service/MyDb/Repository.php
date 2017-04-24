<?php

namespace Egf\Core\Service;

use Egf\Util;

/**
 * Class Repository
 * @todo app
 * @todo Add insert/update/delete queries and run them once.
 */
class Repository {

	/** @var Repository[] $aoInstances */
	protected static $aoInstances = [];

	/** @var string $sTableName */
	protected $sTableName;

	/** @var int Limit to. */
	protected $iDefaultLimitTo = 10000;


	/**
	 * Repository constructor.
	 *
	 * @param string $sTableName
	 */
	public function __construct( $sTableName ) {
		$this->sTableName = $sTableName;
	}

	/**
	 * Get repository instance of given table.
	 *
	 * @param string $sTableName
	 *
	 * @return Repository
	 */
	public static function getInstance( $sTableName ) {
		if ( ! array_key_exists( $sTableName, static::$aoInstances ) ) {
			static::$aoInstances[ $sTableName ] = new self( $sTableName );
		}

		return static::$aoInstances[ $sTableName ];
	}


	/**************************************************************************************************************************************************************
	 *                                                          **         **         **         **         **         **         **         **         **         **
	 * Find                                                       **         **         **         **         **         **         **         **         **         **
	 *                                                          **         **         **         **         **         **         **         **         **         **
	 *************************************************************************************************************************************************************/

	/**
	 * Find one entity by its ID.
	 *
	 * @param integer $iId
	 *
	 * @return object
	 */
	public function find( $iId ) {
		return $this->findOneBy( [ 'id' => $iId ] );
	}

	/**
	 * Find one entity by given conditions.
	 *
	 * @param array $aConditions
	 * @param array $aOrder
	 *
	 * @return object
	 */
	public function findOneBy( array $aConditions, array $aOrder = [] ) {
		return $this->select( $aConditions, $aOrder, 0, 1 );
	}

	/**
	 * Find more entity by given conditions.
	 *
	 * @param array $aConditions
	 * @param array $aOrder
	 * @param int   $iLimitFrom
	 * @param int   $iLimitTo
	 *
	 * @return object[]
	 */
	public function findBy( array $aConditions, array $aOrder = [], $iLimitFrom = 0, $iLimitTo = 0 ) {
		return $this->select( $aConditions, $aOrder, $iLimitFrom, $iLimitTo );
	}

	/**
	 * Find all entity from a table.
	 *
	 * @param array $aOrder
	 *
	 * @return object[]
	 */
	public function findAll( array $aOrder = [] ) {
		return $this->select( [], $aOrder );
	}

	/**
	 * Run a simple select command.
	 *
	 * @param array $aConditions
	 * @param array $aOrder
	 * @param int   $iLimitFrom
	 * @param int   $iLimitTo
	 *
	 * @return mixed Results of Sql query.
	 */
	public function select( array $aConditions = [], array $aOrder = [], $iLimitFrom = 0, $iLimitTo = 0 ) {
		// Select.
		$sSelect = ' SELECT ' . $this->getTableName() . '.* ';

		// From.
		$sFrom = ' FROM ' . $this->getTableName() . ' ';

		// Where.
		$sWhere      = '';
		$aParameters = [];
		$this->addConditionsToSql( $aConditions, $sWhere, $aParameters );

		// Order by.
		$sOrder = '';
		if ( count( $aOrder ) ) {
			foreach ( $aOrder as $sProperty => $sDirection ) {
				$sDirection = strtoupper( trim( $sDirection ) );
				$sOrder .= ' ORDER BY ' . $sProperty . ' ' . ( ( $sDirection === 'ASC' or $sDirection === 'DESC' ) ? $sDirection : 'ASC' ) . ' ';
			}
		}

		// Limit.
		$sLimit = ' LIMIT ' . $iLimitFrom . ' , ' . ( Util::isNaturalNumber( $iLimitTo ) ? $iLimitTo : $this->iDefaultLimitTo ) . '; ';

		// Sql.
		$sSql = $sSelect . $sFrom . $sWhere . $sOrder . $sLimit;

		// Execute.
		if ( $iLimitTo == 1 ) {
			return $this->query( $sSql, $aParameters )->fetch_assoc();
		} else {
			return $this->query( $sSql, $aParameters )->fetch_all( MYSQLI_ASSOC );
		}
	}


	/**************************************************************************************************************************************************************
	 *                                                          **         **         **         **         **         **         **         **         **         **
	 * Insert/Update/Delete                                       **         **         **         **         **         **         **         **         **         **
	 *                                                          **         **         **         **         **         **         **         **         **         **
	 *************************************************************************************************************************************************************/

	/**
	 * Insert a row into database.
	 *
	 * @param array $aParams Row data.
	 *
	 * @return integer Last inserted id.
	 *
	 * @throws \Exception
	 */
	public function insert( array $aParams ) {
		if ( is_array( $aParams ) and count( $aParams ) ) {
			$sProperties = '';
			$sValues     = '';

			foreach ( $aParams as $sProperty => $xParam ) {
				$sProperties .= DbCon::getInstance()->escape( $sProperty ) . ', ';
				$sValues .= '?, ';
			}

			$sQuery = 'INSERT INTO ' . ( $this->getPrefix() . $this->sTableName ) . ' (' . trim( $sProperties, ', ' ) . ') VALUES (' . trim( $sValues, ', ' ) . ');';
			$this->query( $sQuery, $aParams );

			return DbCon::getInstance()->getLastInsertId();

		} else {
			throw new \Exception( 'Invalid repository insert!' );
		}
	}

	/**
	 * Update one or more rows in the database.
	 *
	 * @param array $aConditions Where parameters.
	 * @param array $aParams     New parameters.
	 *
	 * @return integer Number of affected rows.
	 *
	 * @throws \Exception
	 */
	public function update( array $aConditions, array $aParams ) {
		if ( is_array( $aConditions ) && is_array( $aParams ) && count( $aParams ) ) {
			$sSet           = '';
			$aSetParameters = [];

			foreach ( $aParams as $sProperty => $xParam ) {
				if ( strtolower( $sProperty ) !== 'id' ) {
					$sSet .= ' ' . DbCon::getInstance()->escape( $sProperty ) . ' = ?, ';
					$aSetParameters[] = $xParam;
				} else {
					throw new \Exception( 'The ID cannot be changed!' );
				}
			}

			$aWhereParameters = [];
			$this->addConditionsToSql( $aConditions, $sWhere, $aWhereParameters );

			$sQuery = 'UPDATE ' . ( $this->getPrefix() . $this->sTableName ) . ' SET ' . trim( $sSet, ', ' ) . ' ' . $sWhere . ';';
			$this->query( $sQuery, array_merge( $aSetParameters, $aWhereParameters ) );

			return DbCon::getInstance()->getAffectedRows();

		} else {
			throw new \Exception( 'Invalid repository update!' );
		}
	}

	/**
	 * Delete one ore more rows from database.
	 *
	 * @param array $aConditions Where parameters.
	 *
	 * @return int Number of affected rows.
	 *
	 * @throws \Exception
	 */
	public function delete( array $aConditions = [] ) {
		if ( is_array( $aConditions ) && count( $aConditions ) ) {
			$sQuery = 'DELETE FROM ' . ( $this->getPrefix() . $this->sTableName ) . ' ';
			$aWhereParameters = [];
			$this->addConditionsToSql( $aConditions, $sQuery, $aWhereParameters );
			$this->query($sQuery, $aWhereParameters);

			return DbCon::getInstance()->getAffectedRows();
		} else {
			throw new \Exception( 'Invalid repository delete. Conditions are needed!' );
		}
	}


	/**************************************************************************************************************************************************************
	 *                                                          **         **         **         **         **         **         **         **         **         **
	 * Protected                                                  **         **         **         **         **         **         **         **         **         **
	 *                                                          **         **         **         **         **         **         **         **         **         **
	 *************************************************************************************************************************************************************/

	/**
	 * Prefix of tables.
	 * @return string
	 */
	protected function getPrefix() {
		return DbCon::getInstance()->getPrefix();
	}

	/**
	 * Add prefix to table name if it's not already added.
	 *
	 * @return string
	 */
	protected function getTableName() {
		return ( ( substr( $this->sTableName, 0, strlen( $this->getPrefix() ) ) !== $this->getPrefix() ) ? ( $this->getPrefix() . $this->sTableName ) : $this->sTableName );
	}

	/**
	 * It creates the WHERE part of SQL from the conditions array.
	 *
	 * @param array  $aConditions Conditions.
	 * @param string $sSql        [Passed by reference] Sql string.
	 * @param array  $aParameters [Passed by reference] Parameters array.
	 */
	protected function addConditionsToSql( array $aConditions, &$sSql, array &$aParameters ) {
		foreach ( $aConditions as $xKey => $xValue ) {
			$sSql .= ' ' . ( empty( $aParameters ) ? 'WHERE' : 'AND' ) . ' ';

			if ( is_string( $xKey ) && ( is_string( $xValue ) || is_integer( $xValue ) || is_bool( $xValue ) || is_null($xValue) ) ) {
				$xValue = new DbWhere\Equal( $xValue );
			}

			if ( $xValue instanceof DbWhere\Base ) {
				$sSql .= $xKey . ' ' . $xValue->getConditionEquation() . ' ' . $xValue->getOneOrMoreQuestionMark();
				$aParameters[] = $xValue;
			}
			else {
				throw new \Exception( "Invalid select condition!" );
			}
		}
	}

	/**
	 * Sql to statement.
	 *
	 * @param string $sSql
	 * @param array  $aParams
	 *
	 * @return mixed
	 */
	protected function query( $sSql, $aParams ) {
		return DbCon::getInstance()->query( $sSql, $aParams );
	}

}
