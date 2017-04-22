<?php

namespace Egf\Service;

use Egf\Ancient;
use Egf\Util;

/**
 * Class Log
 *
 * todo environment
 */
class Log extends Ancient\Service {

    /** @var resource Opened file. */
    protected $rLog = NULL;

    /**
     * Adds info.
     * @param string $sRow
     * @param int    $iDebugInfo
     */
    public function info($sRow, $iDebugInfo = 0) {
        if (in_array($this->getConfig('environment'), ['dev', 'test'])) {
            $this->add('info', $sRow, $iDebugInfo);
        }
    }

    /**
     * Adds warning.
     * @param string $sRow
     * @param int    $iDebugInfo
     */
    public function warning($sRow, $iDebugInfo = 0) {
        $this->add('warning', $sRow, $iDebugInfo);
    }

    /**
     * Shorten version of warning method. Adds warning.
     * @param string $sRow
     * @param int    $iDebugInfo
     */
    public function warn($sRow, $iDebugInfo = 0) {
        $this->warning($sRow, $iDebugInfo);
    }

    /**
     * Adds error.
     * @param string $sRow
     * @param int    $iDebugInfo
     */
    public function error($sRow, $iDebugInfo = 0) {
        $this->add('error', $sRow, $iDebugInfo);
    }

    /**
     * Adds error and throws exception.
     * @param string $sRow
     * @param int    $iDebugInfo
     * @return \Exception
     */
    public function exception($sRow, $iDebugInfo = 0) {
        $this->add('error', $sRow . PHP_EOL, $iDebugInfo);

        throw new \Exception("\n\n" . preg_replace('/\s+/', ' ', $sRow) . "\n\n");
    }

    /**
     * Adds debug.
     * @param string $sRow
     * @param int    $iDebugInfo Default: 2.
     */
    public function debug($sRow, $iDebugInfo = 2) {
        if (in_array($this->app->getConfig('environment'), ['dev', 'test'])) {
            $this->add('debug', var_export($sRow, TRUE), $iDebugInfo);
        }
    }

    /**
     * New line in log.
     * @param int $iPadding
     * @return string
     */
    public function nl($iPadding = 0) {
        return PHP_EOL . str_repeat(' ', (36 + $iPadding));
    }


    /**
     * Init log.
     */
    protected function init() {
        if ( !$this->rLog) {
            $sDir = Util::slashing("{$this->app->getPathToRoot()}/var/logs", Util::slashingDir);
            $sDate = date('Y_m_d');

            if ( !is_dir($sDir)) {
                mkdir($sDir, 0777, TRUE);
            }

            $sFile = "{$sDir}/{$sDate}.log";
            $this->rLog = fopen($sFile, 'a');
        }
    }

    /**
     * Add to log.
     * @param string $sType
     * @param string $sRow
     * @param int    $iDebugInfo
     */
    protected function add($sType, $sRow, $iDebugInfo = 0) {
        $this->init();

        $sDate = date('Y-m-d H:i:s', time());
        $sLine = "{$sDate} --- {$this->getType($sType)} --- {$sRow}";

        if ($iDebugInfo) {
            $aDebug = debug_backtrace(NULL);
            for ($i = 1; $i <= $iDebugInfo; $i++) {
                if (isset($aDebug[$i])) {
                    $sLine .= $this->nl(4) . "{$aDebug[$i]['class']}{$aDebug[$i]['type']}{$aDebug[$i]['function']} in line {$aDebug[$i]['line']} of file {$aDebug[$i]['file']}";
                }
            }
        }
        $sLine .= PHP_EOL;

        fwrite($this->rLog, $sLine);
    }

    /**
     * Get the type in a fix length string.
     * @param string $sType
     * @return string
     */
    protected function getType($sType) {
        for ($i = strlen($sType); $i < 7; $i++) {
            $sType .= ' ';
        }

        return strtoupper($sType);
    }

}
