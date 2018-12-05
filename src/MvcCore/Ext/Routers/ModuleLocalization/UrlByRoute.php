<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Routers\ModuleLocalization;

trait UrlByRoute
{
	/**
	 * Complete non-absolute, non-localized URL by route instance reverse info.
	 * If there is key `media_version` in `$params`, unset this param before
	 * route URL completing and choose by this param URL prefix to prepend 
	 * completed URL string.
	 * If there is key `localization` in `$params`, unset this param before
	 * route URL completing and place this param as URL prefix to prepend 
	 * completed URL string and to prepend media site version prefix.
	 * Example:
	 *	Input (`\MvcCore\Route::$reverse`):
	 *		`"/products-list/<name>/<color>"`
	 *	Input ($params):
	 *		`array(
	 *			"name"			=> "cool-product-name",
	 *			"color"			=> "red",
	 *			"variant"		=> ["L", "XL"],
	 *			"media_version"	=> "mobile",
	 *			"localization"	=> "en-US",
	 *		);`
	 *	Output:
	 *		`/application/base-bath/m/en-US/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"`
	 * @param \MvcCore\Route|\MvcCore\IRoute &$route
	 * @param array $params
	 * @param string $urlParamRouteName
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute & $route, array & $params = [], $urlParamRouteName = NULL) {
		$moduleParamName = static::URL_PARAM_MODULE;
		$moduleParamDefined = isset($params[$moduleParamName]);
		$currentDomainRouteMatched = $this->currentDomainRoute !== NULL;

		if (
			$route->GetAbsolute() && $moduleParamDefined && $currentDomainRouteMatched &&
			$params[$moduleParamName] !== $this->requestedDomainParams[$moduleParamName]
		) {
			$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
			throw new \InvalidArgumentException(
				"[".$selfClass."] It's not possible to create URL address "
				."to different module/domain for route defined as absolute."
			);
		}

		list ($targetModule, $targetDomainRoute, $domainParamsDefault) = $this->urlGetDomainRouteAndDefaultDomainParams(
			$params, $moduleParamDefined, $currentDomainRouteMatched
		);
		
		$domainUrlBaseSection = NULL;
		if ($targetModule !== NULL) 
			$domainUrlBaseSection = $this->urlGetDomainUrlAndClasifyParamsAndDomainParams(
				$params, $domainParamsDefault, $targetDomainRoute
			);
		

		// get domain with base path URL section, 
		// path with query string URL section 
		// and system params for URL prefixes
		list($urlBaseSection, $urlPathWithQuerySection, $systemParams) = $this->urlByRouteSections(
			$route, $params, $urlParamRouteName
		);

		if ($targetModule !== NULL) 
			$systemParams = array_diff_key($systemParams, $domainParamsDefault);

		// remove localization prefix for non localized routes or
		// remove localization prefix if URL targets top homepage `/` on default language version
		$localizedRoute = $route instanceof \MvcCore\Ext\Routers\Localizations\Route;
		$localizationParamName = static::URL_PARAM_LOCALIZATION;
		$urlPathWithQueryIsHome = NULL;
		if (isset($systemParams[$localizationParamName])) {
			if (!$localizedRoute) {
				unset($systemParams[$localizationParamName]);
			} else {
				// Get `TRUE` if path with query string target homepage - `/` (or `/index.php` - request script name)
				$urlPathWithQueryIsHome = $this->urlIsHomePath($urlPathWithQuerySection);
				if (
					$urlPathWithQueryIsHome && 
					$systemParams[$localizationParamName] === $this->defaultLocalizationStr
				) {
					unset($systemParams[$localizationParamName]);
				}
			}
		}
		
		// create prefixed URL
		return $this->urlByRoutePrefixSystemParams(
			$domainUrlBaseSection ?: $urlBaseSection, 
			$urlPathWithQuerySection, 
			$systemParams, 
			$urlPathWithQueryIsHome
		);
	}
}
