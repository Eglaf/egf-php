<?php

namespace Egf\Core\Cache\Perm;

use Egf\Util;

/**
 * Class PermCache
 * Save data into cache directory.
 * Works only with numbers, strings and arrays.
 * In prod environment it calculates them only once!
 */
class PermCache {

    /** @var boolean Environment. Default is false which is prod. Set to true is dev. */
    protected $bDev = FALSE;

    /** @var string Absolute path to the cache directory. */
    protected $sCacheAbsolutePath = '';


    /**
     * Sets the environment to dev.
     * @return $this
     */
    public function reloadOldCache() {
        $this->bDev = TRUE;

        return $this;
    }

    /**
     * Sets the path to cache directory.
     * @param string $sCacheAbsolutePath
     * @return $this
     */
    public function setCacheAbsolutePath($sCacheAbsolutePath) {
        $this->sCacheAbsolutePath = $sCacheAbsolutePath;

        if ( !is_dir($this->sCacheAbsolutePath)) {
            mkdir($this->sCacheAbsolutePath, 0777, TRUE);
        }

        return $this;
    }


    /**
     * Gives back true if the cache is already exists and false if not.
     * In dev environment it always gives back false!
     * @param string $sKey Cache key.
     * @return bool True if cache exists and prod environment.
     */
    public function has($sKey) {
        return (file_exists($this->getPathFromKey($sKey)) && !$this->bDev);
    }

    /**
     * Sets value to a cache file.
     * @param string $sKey   Cache key.
     * @param mixed  $xValue Data to store.
     * @return $this
     */
    public function set($sKey, $xValue) {
        $rFile = fopen($this->getPathFromKey($sKey), "w");
        $sValue = var_export($xValue, TRUE);
        $sContent = "<?php\n\nreturn {$sValue};\n";

        fwrite($rFile, $sContent);

        return $this;
    }

    /**
     * Gets value from a cache file.
     * @param string $sKey Cache key.
     * @return mixed Stored data.
     */
    public function get($sKey) {
        $sPath = $this->getPathFromKey($sKey);
        if (file_exists($sPath)) {
            return require($sPath);
        }
        else {
            throw new \Exception("Cache file not found on path: {$sPath}");
        }
    }


    /**
     * Get the path to file by the given key.
     * @param string $sKey
     * @return string
     */
    protected function getPathFromKey($sKey) {
        $sKey = Util::slashing($sKey, Util::slashingTrimBoth);
        // Key has directory separator.
        if (strpos($sKey, '/')) {
            $sSubDir = '';
            $aFragments = explode('/', $sKey);
            for ($i = 0; $i < count($aFragments) - 1; $i++) {
                $sSubDir .= "/{$aFragments[$i]}";
            }

            if ( !is_dir($this->sCacheAbsolutePath . $sSubDir)) {
                mkdir($this->sCacheAbsolutePath . $sSubDir, 0777, TRUE);
            }

            return "{$this->sCacheAbsolutePath}/{$sSubDir}/{$aFragments[count($aFragments)-1]}.php";
        }
        // Simple string without directory separator.
        else {
            return "{$this->sCacheAbsolutePath}/{$sKey}.php";
        }
    }

}
