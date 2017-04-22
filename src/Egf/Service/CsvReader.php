<?php

namespace Egf\Service;

use Egf\Util;
use Egf\Ancient;

/**
 * Class CsvReader
 * Simple csv file reader.
 *
 * @todo First row as assoc...
 * @todo Total rework...
 */
class CsvReader extends Ancient\Service {
	
	/**************************************************************************************************************************************************************
	 *                                                          **         **         **         **         **         **         **         **         **         **
	 * Config                                                     **         **         **         **         **         **         **         **         **         **
	 *                                                          **         **         **         **         **         **         **         **         **         **
	 *************************************************************************************************************************************************************/
	
	/** @var string $sPathToFile Path to file from document root. */
	protected $sPathToFile = 'csv/';
	
	/** @var string $sCellSeparator Cell separator in csv. */
	protected $sCellSeparator = ';';
	
	/**
	 * Set path to file.
	 * @param string $sPathToFile
	 * @return CsvReader
	 */
	public function setPathToFile($sPathToFile) {
		$this->sPathToFile = $sPathToFile;
		
		return $this;
	}
	
	/**
	 * Set cell separator.
	 * @param string $sCellSeparator
	 * @return CsvReader
	 */
	public function setCellSeparator($sCellSeparator) {
		$this->sCellSeparator = $sCellSeparator;
		
		return $this;
	}
	
	
	/**************************************************************************************************************************************************************
	 *                                                          **         **         **         **         **         **         **         **         **         **
	 * Do your stuff                                              **         **         **         **         **         **         **         **         **         **
	 *                                                          **         **         **         **         **         **         **         **         **         **
	 *************************************************************************************************************************************************************/
	
	/**
	 * Load and process file.
	 * @param string $sFileName
	 * @return array
	 */
	public function loadFile($sFileName) {
		$sFile   = Util::concatWithDirSep($_SERVER['DOCUMENT_ROOT'], $this->sPathToFile, Util::addFileExtensionIfNeeded($sFileName, 'csv'));
		$aResult = [];
		
		// Check file.
		if (file_exists($sFile)) {
			// Iterate rows of csv.
			if (($rFile = fopen($sFile, "r")) !== FALSE) {
				/** @var array $aRow The content of row. */
				while (($aRow = fgetcsv($rFile, NULL, $this->sCellSeparator)) !== FALSE) {
					$aResultRow = [];
					for ($c = 0; $c < count($aRow); $c ++) {
						$aResultRow[] = $aRow[ $c ];
					}
					$aResult[] = $aResultRow;
				}
			}
			
			$this->app->get('log')->info("Csv file loaded and processed. File: {$sFile}");
		}
		// There is no file.
		else {
			$this->app->get('log')->warn("File not found: {$sFile}");
		}
		
		return $aResult;
	}
}