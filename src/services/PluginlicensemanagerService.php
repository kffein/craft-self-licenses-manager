<?php
/**
 * plugin-license-manager plugin for Craft CMS 3.x
 *
 * Plugin license manager
 *
 * @link      kffein.com
 * @copyright Copyright (c) 2018 Kffein
 */

namespace kffein\pluginlicensemanager\services;

use kffein\pluginlicensemanager\Pluginlicensemanager;
use craft\base\Component;
use Craft;

/**
 * @author    Kffein
 * @package   Pluginlicensemanager
 * @since     1.0.0
 */
class PluginlicensemanagerService extends Component
{
    private const API_HOST_URL = 'https://api.craftcms.com/v1/';
    private const LICENSE_STATUS_VALID = 'valid';
    const SESSION_FLASH_KEY = 'plugin-license-manager';

    private $settings;

    // Public Methods
    // =========================================================================

    public function __construct()
    {
        $this->settings = Pluginlicensemanager::$plugin->getSettings();
    }

    /**
     * Return the list of plugins accessible via Craft id api
     * that is not registered with license
     *
     * @return array
     */
    public function getUnregisteredPlugins() : array
    {
        $unregisteredPluginsHandle = [];

        // Fetch the Api to return uniq plugins handle
        $ownPluginsHandle = $this->getPluginsHandle();

        // If API call has error, error set to flash and quit method
        if ($this->hasError()) {
            return null;
        }

        // Return the plugins list from the craft DB
        $allPlugins = Craft::$app->plugins->allPluginInfo;

        // Filter plugins DB list to return only own plugin from the API
        $plugins = array_filter($allPlugins, function ($pluginHandle) use ($ownPluginsHandle) {
            return in_array($pluginHandle, $ownPluginsHandle);
        }, ARRAY_FILTER_USE_KEY);

        // Filter out plugin with license status is invalid
        $unregisteredPlugins = array_filter($plugins, function ($plugin) {
            return $plugin['licenseKeyStatus'] !== self::LICENSE_STATUS_VALID;
        });

        // Loop plugins and return name + handle data
        foreach ($unregisteredPlugins as $plugin) {
            $unregisteredPluginsHandle[] = [
                'handle' => $plugin['moduleId'],
                'name' => $plugin['name']
            ];
        }

        return $unregisteredPluginsHandle;
    }

    /**
     * Generate the license with the Craft id API
     * Set the license to the plugin
     * Change the plugin status to valid
     *
     * @param string $email
     * @param string $pluginHandle
     * @return boolean
     */
    public function generateAndActivatePluginLicense(string $email, string $pluginHandle) : bool
    {
        $result = $this->generateLicense($email, $pluginHandle);

        if (isset($result->message)) {
            return false;
        }

        $licenseKey = $result->license->key;

        Craft::$app->plugins->setPluginLicenseKey($pluginHandle, $licenseKey);
        Craft::$app->plugins->setPluginLicenseKeyStatus($pluginHandle, 'valid');

        return true;
    }

    /**
     * Return plugin that is not returner by the Craft id API
     * Api return only data for license. If no license for a plugin exist yet, it will not be returned by the api
     * To fix this, we looked into the composer.json require library, and get library with the same author as the username plugin
     * Return plugin that not exist in craft id api
     *
     * @return void
     */
    public function getPluginsHandleWithoutApiResult()
    {
        $apiPlugins = $this->getPluginsHandle();
        $composerPlugins = $this->getComposerPluginshandle();
        $availablePlugins = Craft::$app->plugins->allPluginInfo;
        $availablePluginsHandle = [];

        foreach ($availablePlugins as $pluginHandle => $plugin) {
            $availablePluginsHandle[] = $pluginHandle;
        }

        $composerPlugins = array_filter($composerPlugins, function ($pluginHandle) use ($availablePluginsHandle) {
            return in_array($pluginHandle, $availablePluginsHandle);
        });

        $missingApiPluginsHandle = array_filter($composerPlugins, function ($composerPlugin) use ($apiPlugins) {
            return (!in_array($composerPlugin, $apiPlugins));
        });

        return $missingApiPluginsHandle;
    }

    /**
     * Validate if settings is valid by request Craft id api
     *
     * @return boolean
     */
    public function validateSettingsWithApi() : bool
    {
        $result = $this->getLicenses();
        return (!isset($result->message));
    }

    /**
    * Fetch api and return uniq plugins handle
    *
    * @return array
    */
    private function getPluginsHandle() : ?array
    {
        $page = 1;
        $licensesHandles = [];
        $licensesInfo = $this->getLicenses($page);
        $this->validateRequest($licensesInfo);

        if ($this->hasError()) {
            return null;
        }

        $totalPages = $licensesInfo->totalPages;
        $licenses = $licensesInfo->licenses;

        // Loop each license and save uniq plugin handle
        foreach ($licenses as $license) {
            if (!in_array($license->pluginHandle, $licensesHandles)) {
                $licensesHandles[] = $license->pluginHandle;
            }
        }

        // If pagination, continue to find plugin handles
        while ($page < $totalPages) {
            $licensesInfo = $this->getLicenses($page);
            $licenses = $licensesInfo->licenses;
            foreach ($licenses as $license) {
                if (!in_array($license, $licensesHandles)) {
                    $licensesHandles[] = $license;
                }
            }
            $page++;
        }

        return $licensesHandles;
    }

    /**
     * Fetch licenses with Craft license API
     *
     * @param integer $page
     * @return array
     */
    private function getLicenses(int $page = 1) : object
    {
        $url = self::API_HOST_URL . 'plugin-licenses?page=' . $page;
        $result = $this->makeRequest($url, [], 'GET');
        $this->validateRequest($result);
        return $result;
    }

    /**
     * Generate license with Craft id API
     *
     * @param string $email
     * @param string $pluginHandle
     * @return object
     */
    private function generateLicense(string $email, string $pluginHandle) : object
    {
        $options = [
            'edition' => 'standard',
            'plugin' => $pluginHandle,
            'email' => $email
          ];

        $url = self::API_HOST_URL . 'plugin-licenses';

        $result = $this->makeRequest($url, $options);

        return $result;
    }

    /**
     * Read composer.json file and return node require + require-dev
     * with author as same as username
     *
     * @return void
     */
    private function getComposerPluginshandle()
    {
        $composerPath = Craft::$app->composer->jsonPath;
        if ($composerPath === null) {
            return [];
        }
        $composerContent = file_get_contents($composerPath);
        $composerContent = json_decode($composerContent);
        $username = $this->settings->username;
        $pluginsPaths = [];
        $pluginsHandle = [];

        $composerPluginNodes = ['require', 'require-dev'];

        foreach ($composerPluginNodes as $node) {
            $plugins = isset($composerContent->$node) ? (array) $composerContent->$node : [];
            $pluginsPaths = array_merge($pluginsPaths, array_filter($plugins, function ($pluginPath) use ($username) {
                $data = explode('/', $pluginPath);
                return $data[0] === $username;
            }, ARRAY_FILTER_USE_KEY));
        }

        foreach ($pluginsPaths as $pluginPath => $plugin) {
            $data = explode('/', $pluginPath);
            if (isset($data[1])) {
                $pluginsHandle[] = $data[1];
            }
        }

        return $pluginsHandle;
    }

    /**
     * Valid API call response
     *
     * @param [type] $response
     * @return void
     */
    private function validateRequest($response) : void
    {
        if (isset($response->message)) {
            Craft::$app->getSession()->set(self::SESSION_FLASH_KEY, $response->message);
        }
    }

    /**
     * Get if error exist
     *
     * @return boolean
     */
    private function hasError() : bool
    {
        $errors = Craft::$app->getSession()->get(self::SESSION_FLASH_KEY);
        return (!empty($errors));
    }

    /**
     * Make http request for Craft API
     *
     * @param string $url
     * @param array $options
     * @param string $method
     * @return void
     */
    private function makeRequest(string $url, array $options = [], string $method = 'POST')
    {
        $username = $this->settings->username;
        $apiKey = $this->settings->apiKey;
        $payload = json_encode($options);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$apiKey}");
        curl_setopt($ch, CURLOPT_POST, $method === 'POST' ? 1 : 0);

        if (!empty($options)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return json_decode($output);
    }
}
