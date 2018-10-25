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

trait RewriteRoutingChecks
{
	protected function rewriteRoutingCheckRoute (\MvcCore\IRoute & $route, array $additionalInfo) {
		list ($requestMethod, $localizationInRequest, $routeIsLocalized, $localizationRoutesSkipping) = $additionalInfo;

		$routeMethod = $route->GetMethod();
		if ($routeMethod !== NULL && $routeMethod !== $requestMethod) return TRUE;

		$modules = $route->GetAdvancedConfigProperty(\MvcCore\Ext\Routers\Modules\IRoute::CONFIG_ALLOWED_MODULES);
		if (is_array($modules) && !in_array($this->currentModule, $modules)) return TRUE;

		// skip localized routes matching when request has no localization in path
		if ($routeIsLocalized && $localizationInRequest === FALSE) {
			// but do not skip localized routes matching when request has no localization in path and:
			// - when method is post and router has not allowed to process other methods than GET
			// - or when method is anything and router has allowed to process other methods than GET
			if ($localizationRoutesSkipping) return TRUE;
		}

		if ($routeIsLocalized === FALSE && isset($this->allowNonLocalizedRoutes) && $this->allowNonLocalizedRoutes === FALSE) 
			return TRUE;

		return FALSE;
	}
}
