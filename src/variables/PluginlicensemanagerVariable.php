<?php
/**
 * plugin-license-manager plugin for Craft CMS 3.x
 *
 * Plugin license manager
 *
 * @link      kffein.com
 * @copyright Copyright (c) 2018 Kffein
 */

namespace kffein\pluginlicensemanager\variables;

use kffein\pluginlicensemanager\Pluginlicensemanager;
use Craft;

/**
 * @author    Kffein
 * @package   Pluginlicensemanager
 * @since     1.0.0
 */
class PluginlicensemanagerVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param null $optional
     * @return string
     */
    public function unregisteredPlugins()
    {
        return Pluginlicensemanager::getInstance()->pluginlicensemanagerService->getUnregisteredPlugins();
    }

    public function pluginsHandleWithoutApiResult()
    {
        return Pluginlicensemanager::getInstance()->pluginlicensemanagerService->getPluginsHandleWithoutApiResult();
    }

    public function settings()
    {
        return Pluginlicensemanager::getInstance()->getSettings();
    }

    public function errors()
    {
        $flashKey = Pluginlicensemanager::getInstance()->pluginlicensemanagerService::SESSION_FLASH_KEY;
        $errors = Craft::$app->getSession()->get($flashKey);
        Craft::$app->getSession()->set($flashKey, null);
        return $errors;
    }
}
