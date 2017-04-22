<?php

namespace Egf;

/**
 * @url http://phpenthusiast.com/blog/how-to-autoload-with-composer
 * composer dump-autoload -o
 *
 * todo App and AppDev extends AncientApp
 * todo Config: renamedServices.json ?
 */
class App {

    /** @var string $sPathToRoot Path to the root directory. */
    protected $sPathToRoot = '';

    /** @var object[] $aGlobalConfigs Data from the global root/config directory. */
    protected $aoGlobalConfigs = [];

    /** @var object[] Store services data (without the class) until it needs to be loaded. */
    protected $aoInitiativeServices = [];

    /** @var object[] Loaded services. */
    protected $aoServices = [];


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Getters                                                    **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

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
            $oService = $this->aoInitiativeServices[$sService];
            $sClass = Util::slashing("{$oService->bundle}\\{$oService->class}", Util::slashingNs | Util::slashingAddLeft);

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
        $xValue = $this->aoGlobalConfigs[$sType]->{$sKey};

        return (isset($xValue) ? $xValue : $xDefault);
    }


    /**************************************************************************************************************************************************************
     *                                                          **         **         **         **         **         **         **         **         **         **
     * Init                                                       **         **         **         **         **         **         **         **         **         **
     *                                                          **         **         **         **         **         **         **         **         **         **
     *************************************************************************************************************************************************************/

    /**
     * App constructor.
     */
    public function __construct() {
        try {
            $this
                ->loadPathToRoot()
                ->loadGlobalConfigs()
                ->loadEnvironment()
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
     * Loads config files.
     * @return $this
     * @todo Read cache... load only if dev or not exists!
     * @todo Assoc array instead?
     */
    protected function loadGlobalConfigs() {
        foreach (['configs', 'parameters', 'bundles'] as $sConf) {
            $sConfFile = "{$this->getPathToRoot()}/config/{$sConf}.json";
            if (file_exists($sConfFile)) {
                $this->aoGlobalConfigs[$sConf] = json_decode(file_get_contents($sConfFile));
            }
        }

        return $this;
    }

    /**
     * Turn on or off the error messages, depending on the environment config. Throw exception when no dev is set!
     * @return $this
     * @todo ez igy szar... talalj ki jobbat...
     */
    protected function loadEnvironment() {
        // Prod.
        if ($this->getConfig('environment') === 'prod') {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
        // Dev, test.
        elseif (in_array($this->getConfig('environment'), ['dev', 'test'])) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }
        // Neither.
        else {
            throw new \Exception('The environment has to be "dev", "test" or "prod" in config.json!');
        }

        return $this;
    }

    /**
     * Load configured bundles.
     * @return $this
     */
    protected function loadBundles() {
        $aAutoloadPsr4 = require("{$this->sPathToRoot}/vendor/composer/autoload_psr4.php");

        foreach ($this->aoGlobalConfigs['bundles']->bundles as $sBundle) {
            $this->preloadServicesOfBundle($sBundle, $aAutoloadPsr4);
        }

        return $this;
    }

    /**
     * Preload services from the bundle and store the name and data of them. Load class only on first use.
     * @param string $sBundle       The name of bundle.
     * @param array  $aAutoloadPsr4 Namespaces from composer autoLoader.
     */
    protected function preloadServicesOfBundle($sBundle, array $aAutoloadPsr4) {
        $sPathToBundle = $this->getBundlePath($sBundle, $aAutoloadPsr4);
        $sPathToServicesConfig = realpath("{$sPathToBundle}/config/services.json");

        if ($sPathToServicesConfig) {
            $oBundleServices = json_decode(file_get_contents($sPathToServicesConfig));

            if ($oBundleServices && is_array($oBundleServices->services)) {
                foreach ($oBundleServices->services as $iNum => $oService) {
                    if (isset($oService->name) && strlen($oService->name) && isset($oService->class) && strlen($oService->class)) {
                        $oService->bundle = $sBundle;

                        $this->aoInitiativeServices[$oService->name] = $oService;
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
    }

    /**
     * Gets the path to the bundle.
     * @param string $sBundle       Namespace of bundle.
     * @param array  $aAutoloadPsr4 Namespaces from composer autoLoader.
     * @return string Path to bundle.
     * @todo Test on Unix.
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
