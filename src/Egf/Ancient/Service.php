<?php

namespace Egf\Ancient;

use Egf\App;

/**
 * Class AncientService
 */
abstract class Service {

    /** @var App */
    protected $app;


    /**
     * CsvReader constructor.
     * @param App $app
     */
    public function __construct(App $app) {
        $this->app = $app;

        $this->init();
    }

    /**
     * Does stuff in construct.
     */
    protected function init() {
    }


    /**
     * Gets a service.
     * @param string $sService
     * @return object
     */
    protected function get($sService) {
        return $this->app->get($sService);
    }

    /**
     * Gets a config value.
     * @param string $sKey
     * @param mixed  $xDefault
     * @return mixed
     */
    protected function getConfig($sKey, $xDefault = NULL) {
        return $this->app->getConfig($sKey, $xDefault);
    }

    /**
     * Get a parameter value.
     * @param string $sKey
     * @param mixed  $xDefault
     * @return mixed
     */
    protected function getParam($sKey, $xDefault = NULL) {
        return $this->app->getParam($sKey, $xDefault);
    }


    /**
     * Prevent duplications.
     */
    public function __clone() {
    }

}
