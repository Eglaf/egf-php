<?php

namespace Egf\Service\MyDb;

use Egf\Util;

/**
 * Class MyDb
 */
class MyDb extends \Egf\Ancient\Service {


    /** @var \mysqli */
    protected $oConnection = NULL;

    /** @var int Generated ID of the last inserted row. */
    protected $iLastInsertId = 0;

    /** @var int Number of affected rows. */
    protected $iAffectedRows = 0;

    /** @var string Path to the config file. */
    protected $sPathToConfig = '/config/';


    /**
     * Initialize.
     */
    public function init() {
        $aConfig = $this->loadConfig();
        $this->oConnection = new \mysqli($aConfig['host'], $aConfig['username'], $aConfig['password'], $aConfig['database']);

        if (mysqli_connect_error()) {
            $this->get('log')->exception('Failed to connect to MySql! ' . mysqli_connect_error());
        }

        mysqli_set_charset($this->oConnection, "utf8");
    }


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Use connection                                             **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Run query.
     * @param string     $sQuery  The Sql query.
     * @param array|NULL $aParams Associative array of optional parameters. Array['type'] can be one ore more of these: i, s, d, b. Array['value'] is the searched value.
     * @return array|\mysqli_result|bool
     */
    public function query($sQuery, array $aParams = []) {
        $oStmt = $this->getConnection()->prepare($sQuery);
        if ($oStmt) {
            if (is_array($aParams) && count($aParams)) {
                $aValues = [0 => ''];
                foreach ($aParams as $aParam) {
                    if ($aParam instanceof DbWhere\Base) {
                        if (is_array($aParam->getValue())) {
                            foreach ($aParam->getValue() as $xVal) {
                                $aValues[0] .= $aParam->getType();
                                $aValues[] = $xVal;
                            }
                        }
                        else {
                            $aValues[0] .= $aParam->getType();
                            $aValues[] = $aParam->getValue();
                        }
                    }
                    elseif (is_array($aParam) && isset($aParam['type']) && (isset($aParam['value']) || is_null($aParam['value']))) {
                        $aValues[0] .= $aParam['type'];
                        $aValues[] = $aParam['value'];
                    }
                    else {
                        $aValues[0] .= 's';
                        $aValues[] = $aParam;
                    }
                }

                // Bind parameters to statement.
                call_user_func_array([$oStmt, 'bind_param'], $this->referenceValues($aValues));

            }
            $oStmt->execute();
            $xResult = $oStmt->get_result();

            $this->iLastInsertId = $this->getConnection()->insert_id;
            $this->iAffectedRows = $this->getConnection()->affected_rows;

            $oStmt->close();

            return $xResult;
        }
        else {
            $this->get('log')->exception("Invalid Sql query! {$this->get('log')->nl()} {$sQuery} {$this->get('log')->nl()}" . var_export($aParams, TRUE));
        }
    }

    /**
     * Get connection.
     * @return \mysqli
     */
    public function getConnection() {
        return $this->oConnection;
    }

    /**
     * Escape a string.
     * @param mixed $xVar Unsecured string.
     * @return string Secured string.
     */
    public function escape($xVar) {
        return $this->getConnection()->real_escape_string($xVar);
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

    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Protected                                                  **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Load db login information from Config file.
     * @return array
     * @throws \Exception
     * @deprecated
     */
    protected function loadConfig() {
        $aResult = [];

        $sConfigFile = Util::trimSlash($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . Util::trimShash($this->sPathToConfig) . '/database.conf';
        $rFile = fopen($sConfigFile, 'r');
        if (is_resource($rFile)) {
            while ( !feof($rFile)) {
                $sLine = fgets($rFile);
                $aLine = explode(':', $sLine);

                if ($aLine and strlen(trim($aLine[0]))) {
                    $aResult[trim($aLine[0])] = trim($aLine[1]);
                }
            }
            fclose($rFile);
        }
        else {
            throw new \Exception("Invalid db config file!");
        }

        if ( !isset($aResult['host']) || !isset($aResult['username']) || !isset($aResult['password']) || !isset($aResult['database'])) {
            throw new \Exception('Invalid database Config!');
        }

        return $aResult;
    }

    /**
     * Transform parameter values into reference.
     * @param array $arr
     * @return array
     */
    protected function referenceValues($arr) {
        // Reference is required for PHP 5.3+
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = array();
            foreach ($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }

            return $refs;
        }

        return $arr;
    }

}
