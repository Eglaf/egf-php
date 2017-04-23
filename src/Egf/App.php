<?php

namespace Egf;

use \Egf\Core\Cache\Perm\PermCache;

/**
 * Egf App.
 */
class App {

    /** @var bool Decide if it runs in dev environment. */
    protected $bDev = FALSE;

    /** @var PermCache $oPermCache Store Permanently data in cache directory. In case of prod environment, it'll be calculated only once. */
    protected $oPermCache = NULL;

    /** @var string $sPathToRoot Path to the root directory. */
    protected $sPathToRoot = '';

    /** @var object[] $aGlobalConfigs Data from the global root/config directory. */
    protected $aGlobalConfigs = [];

    /** @var object[] Store services data (without the class) until it needs to be loaded. */
    protected $aInitiativeServices = [];

    /** @var object[] Loaded services. */
    protected $aoServices = [];


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Getters                                                    **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * Gets true if dev environment, false otherwise.
     * @return bool
     */
    public function isDev() {
        return $this->bDev;
    }

    /**
     * Gets the path to project root. Works only if the Egf\App is in the vendor/ or /src somewhere.
     * @return string
     */
    public function getPathToRoot() {
        return $this->sPathToRoot;
    }

    /**
     * Gets a service.
     * @param $sService
     * @return object
     * @throws \Exception
     */
    public function get($sService) {
        if ( !(isset($this->aoServices[$sService]))) {
            $aService = $this->aInitiativeServices[$sService];
            $sClass = Util::slashing($aService['bundle'] . "\\" . $aService['class'], Util::slashingBackslash | Util::slashingAddLeft);

            $this->aoServices[$sService] = new $sClass($this);
        }

        return $this->aoServices[$sService];
    }

    /**
     * Gets a value from the config.json. It has the project dependent data.
     * @param string $sKey     Key in config.json.
     * @param mixed  $xDefault The default value if not exists.
     * @return mixed
     */
    public function getConfig($sKey, $xDefault = NULL) {
        return $this->getConfigOrParam('configs', $sKey, $xDefault);
    }

    /**
     * Gets a value from the parameters.json. It has the localhost dependent data.
     * @param string $sKey     Key in parameters.json.
     * @param mixed  $xDefault The default value if not exists.
     * @return mixed
     */
    public function getParam($sKey, $xDefault = NULL) {
        return $this->getConfigOrParam('parameters', $sKey, $xDefault);
    }

    /**
     * Get the config or parameter value.
     * @param string $sType    Load from file. Config or parameters.
     * @param string $sKey     The key of value.
     * @param mixed  $xDefault The default if it doesn't exist.
     * @return mixed Value.
     */
    protected function getConfigOrParam($sType, $sKey, $xDefault = NULL) {
        $xValue = $this->aGlobalConfigs[$sType][$sKey];

        return (isset($xValue) ? $xValue : $xDefault);
    }


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Init                                                       **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * App constructor.
     * @param boolean $bDev Dev environment.
     */
    public function __construct($bDev = FALSE) {
        $this->bDev = $bDev;

        try {
            $this
                ->loadPathToRoot()
                ->loadPermCache()
                ->loadGlobalConfigs()
                ->loadErrorReporting()
                ->loadBundles();

            $this->get('log')->info('Egf\App is running.');
        }
        catch (\Exception $ex) {
            die("<br />Can not initialize Egf\\App because: <br>{$ex->getMessage()}<br />");
        }
    }

    /**
     * Looks for the root directory. Works only if the Egf\App is in the vendor/ or /src somewhere.
     * @return $this
     */
    protected function loadPathToRoot() {
        $sPathToRoot = '';
        foreach (explode(DIRECTORY_SEPARATOR, __DIR__) as $sPathFragment) {
            if (in_array($sPathFragment, ['vendor', 'src'])) {
                break;
            }
            $sPathToRoot .= $sPathFragment . '/';
        }

        $this->sPathToRoot = realpath($sPathToRoot) . '/';

        return $this;
    }

    /**
     * Loads the permanent cache class.
     * @return $this
     *
     * todo Always set to dev...
     */
    protected function loadPermCache() {
        $this->oPermCache = (new PermCache())
            ->setCacheAbsolutePath("{$this->sPathToRoot}/var/cache/perm");

        if ($this->bDev) {
            $this->oPermCache->reloadOldCache();
        }

        return $this;
    }

    /**
     * Loads config files.
     * @return $this
     */
    protected function loadGlobalConfigs() {
        if ($this->oPermCache->needToRecalculate('egf/global-config')) {
            $aGlobalConfig = [];
            foreach (['configs', 'parameters', 'bundles'] as $sConf) {
                $sConfFile = "{$this->getPathToRoot()}/config/{$sConf}.json";
                if (file_exists($sConfFile)) {
                    $aGlobalConfig[$sConf] = json_decode(file_get_contents($sConfFile), TRUE);
                }
            }
            $this->oPermCache->set('egf/global-config', $aGlobalConfig);
        }

        $this->aGlobalConfigs = $this->oPermCache->get('egf/global-config');

        return $this;
    }

    /**
     * Turn on or off the error messages, depending on the environment config. Throw exception when no dev is set!
     * @return $this
     */
    protected function loadErrorReporting() {
        // Dev.
        if ($this->bDev) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);

        }
        // Prod.
        else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }

        return $this;
    }

    /**
     * Load configured bundles.
     * @return $this
     */
    protected function loadBundles() {
        $aAutoloadPsr4 = require("{$this->sPathToRoot}/vendor/composer/autoload_psr4.php");

        // Load services.
        if ($this->oPermCache->needToRecalculate('egf/services')) {
            $aInitiativeServices = [];
            foreach ($this->aGlobalConfigs['bundles']['bundles'] as $sBundle) {
                $aInitiativeServices = array_merge($aInitiativeServices, $this->getInitiativeServicesOfBundle($sBundle, $aAutoloadPsr4));
            }
            $this->oPermCache->set('egf/services', $aInitiativeServices);
        }
        $this->aInitiativeServices = $this->oPermCache->get('egf/services');

        return $this;
    }

    /**
     * Get initiative services from the bundle.
     * @param string $sBundle       The name of bundle.
     * @param array  $aAutoloadPsr4 Namespaces from composer autoLoader.
     * @return array Service configs of bundle.
     */
    protected function getInitiativeServicesOfBundle($sBundle, array $aAutoloadPsr4) {
        $aInitiativeServices = [];

        $sPathToBundle = $this->getBundlePath($sBundle, $aAutoloadPsr4);
        $sPathToServicesConfig = realpath("{$sPathToBundle}/config/services.json");

        if ($sPathToServicesConfig) {
            $aBundleServices = json_decode(file_get_contents($sPathToServicesConfig), TRUE);

            if ($aBundleServices && is_array($aBundleServices['services'])) {
                foreach ($aBundleServices['services'] as $iNum => $aService) {
                    if (isset($aService['name']) && strlen($aService['name']) && isset($aService['class']) && strlen($aService['class'])) {
                        $aService['bundle'] = $sBundle;

                        $aInitiativeServices[$aService['name']] = $aService;
                    }
                    else {
                        $sNum = Util::stNdRdTh($iNum + 1);

                        throw new \Exception("Config of the {$sNum} service in {$sBundle} bundle is invalid. It needs a non empty name and path property.");
                    }
                }
            }
            else {
                throw new \Exception("Invalid json of {$sPathToServicesConfig}");
            }
        }

        return $aInitiativeServices;
    }

    /**
     * Gets the path to the bundle.
     * @param string $sBundle       Namespace of bundle.
     * @param array  $aAutoloadPsr4 Namespaces from composer autoLoader.
     * @return string Path to bundle.
     */
    protected function getBundlePath($sBundle, array $aAutoloadPsr4) {
        // Looks for the bundle in src directory.
        $sPath = realpath("{$this->getPathToRoot()}/src/{$sBundle}");
        if ($sPath) {
            return $sPath;
        }

        // Looks for the bundle in vendor directory.
        foreach ($aAutoloadPsr4 as $sNs => $aPaths) {
            $sNs = trim(str_replace('\\', '/', $sNs), '/');
            $sBundle = trim(str_replace('\\', '/', $sBundle), '/');

            if ($sNs === $sBundle) {
                foreach ($aPaths as $sOneOfThePaths) {
                    $sPath = realpath($sOneOfThePaths);
                    if ($sPath) {
                        return $sPath;
                    }
                }
            }
        }

        throw new \Exception("The bundle {$sBundle} not found!");
    }

}
