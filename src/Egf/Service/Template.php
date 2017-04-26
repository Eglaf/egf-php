<?php

namespace Egf\Service;

use Egf\Util;

/**
 * Class Template
 */
class Template extends \Egf\Ancient\Service {

	/**
	 * Get the Html code of a template with variables.
	 * @param string  $sTemplate   The path to the template. If it has a doubleDot in it, then it's a template in a bundle. Otherwise it's a path from the root.
	 * @param mixed[] $aAttributes Parameters of template.
	 * @return string
	 */
	public function render($sTemplate, array $aAttributes = []) {
		$sTemplate = $this->getTemplatePath($sTemplate);

		extract($aAttributes);
		ob_start();
		include $sTemplate;

		return ob_get_clean();
	}

	/**
	 * Get the path to the template.
	 * @param string $sTemplate
	 * @return string
	 */
	protected function getTemplatePath($sTemplate) {
		// Template in a Bundle.
		if (strpos($sTemplate, ':') !== FALSE) {
			$aFragments = explode(':', $sTemplate);
			$sBundlePath = $this->app->getBundle(Util::slashing($aFragments[0]))['path'];

			$sTemplate = "{$sBundlePath}/view/{$aFragments[1]}";
		}
		// Template is from the project root.
		else {
			$sTemplate = "{$this->app->getPathToRoot()}/{$sTemplate}";
		}

		// Php extension.
		$sTemplate = Util::addFileExtensionIfNeeded($sTemplate, 'php');

		// Check template file.
		if (file_exists($sTemplate)) {
			return $sTemplate;
		}
		// There is no file.
		else {
			throw $this->getService('log')->exception("Invalid template path: {$sTemplate}");
		}
	}

}
