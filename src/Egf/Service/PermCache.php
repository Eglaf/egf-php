<?php

namespace Egf\Service;

use Egf\Core\Cache\Perm\PermCache as CorePermCache;

/**
 * Class PermCache
 * Store cache permanently.
 */
class PermCache extends \Egf\Ancient\Service {

    /** @var CorePermCache $oPermCache */
    protected $oPermCache = NULL;

    
    /**
     * Gives back true if the cache is already exists and false if not.
     * In dev environment it always gives back false!
     * @param string $sKey Cache key.
     * @return bool True if cache exists and prod environment.
     */
    public function has($sKey) {
        return $this->oPermCache->has($sKey);
    }

    /**
     * Sets value to a cache file.
     * @param string $sKey Cache key.
     * @param mixed  $xValue Data to store.
     * @return $this
     */
    public function set($sKey, $xValue) {
        $this->oPermCache->set($sKey, $xValue);

        return $this;
    }

    /**
     * Gets value from a cache file.
     * @param string $sKey Cache key.
     * @return mixed Stored data.
     */
    public function get($sKey) {
        return $this->oPermCache->get($sKey);
    }


    /**
     * Init PermCache from the Egf\Core.
     */
    protected function init() {
        $this->oPermCache = (new CorePermCache())
            ->setCacheAbsolutePath("{$this->app->getPathToRoot()}/var/cache/perm");

        if ($this->app->isDev()) {
            $this->oPermCache->reloadOldCache();
        }
    }

}