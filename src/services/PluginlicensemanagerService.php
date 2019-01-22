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
    const LICENSE_STATUS_VALID = 'valid';
    const I18N_DEFAULT_LOCALE = 'en';
    const SESSION_FLASH_KEY = 'plugin-license-manager';

    private $settings;
    private $apiEndPoint;

    // Public Methods
    // =========================================================================

    public function __construct()
    {
        $this->settings = Pluginlicensemanager::$plugin->getSettings();
        $this->apiEndPoint = Craft::$app->pluginStore->craftApiEndpoint . '/';
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
    public function generateAndActivatePluginLicense(string $pluginHandle, string $editionHandle) : bool
    {
        $result = $this->generateLicense($this->settings->licenseEmail, $pluginHandle, $editionHandle);

        if (isset($result->message)) {
            return false;
        }

        $licenseKey = $result->license->key;

        Craft::$app->plugins->setPluginLicenseKey($pluginHandle, $licenseKey);
        Craft::$app->plugins->setPluginLicenseKeyStatus($pluginHandle, 'valid');

        return true;
    }

    /**
     * Get the developer information
     *
     * @return array
     */
    public function getDeveloperInfo()
    {
        $apiPluginsData = (object) Craft::$app->api->getPluginStoreData();
        $apiPlugins = $apiPluginsData->plugins;
        $pluginForDeveloper = null;
        $developerName = $this->settings->developerName;
        foreach ($apiPlugins as $plugin) {
            if (strtolower($plugin['developerName']) === strtolower($developerName)) {
                $pluginForDeveloper = $plugin;
                break;
            }
        }
        if ($pluginForDeveloper === null) {
            return null;
        }

        $developerId = $pluginForDeveloper['developerId'];
        $developerInfo = Craft::$app->api->getDeveloper($developerId);
        return (!isset($developerInfo->error)) ? $developerInfo : null;
    }

    /**
     * Return the plugins list for the developer defined in settings
     * Filter plugins that exist on DB (register in composer)
     * Filter plugins that need a license (Not free)
     * Filter plugins with no license yet
     * Format the data for twig template
     *
     * @return array
     */
    public function getUnregisteredPlugins() : array
    {
        $unregisteredPluginsData = [];

        // Get own plugins and return the handle
        $ownPlugins = $this->getPluginsByDeveloperName();

        if (empty($ownPlugins)) {
            Craft::$app->getSession()->setError(Craft::t('plugin-license-manager', 'errors__nopluginsapifound', [], self::I18N_DEFAULT_LOCALE));
            return [];
        }

        $ownPluginsHandle = array_map(function ($plugin) {
            return $plugin['handle'];
        }, $ownPlugins);

        // Return the plugins list from the craft DB
        $allPlugins = Craft::$app->plugins->allPluginInfo;
        $allPluginsHandle = [];
        foreach ($allPlugins as $plugin) {
            $allPluginsHandle[] = $plugin['moduleId'];
        }

        // Filter plugins DB list to return only own plugin from the API
        $plugins = array_filter($ownPlugins, function ($plugin) use ($allPluginsHandle) {
            return in_array($plugin['handle'], $allPluginsHandle);
        });

        // Filter license needed plugins by validating existing editions prices
        $plugins = array_filter($plugins, function ($plugin) {
            $pluginNeedLicense = false;
            $editions = $plugin['editions'];
            foreach ($editions as $edition) {
                if ($edition['price'] !== null || $edition['renewalPrice'] !== null) {
                    $pluginNeedLicense = true;
                    break;
                }
            }
            return $pluginNeedLicense;
        });

        // Filter out plugin with license status is invalid
        // $unregisteredPlugins = array_filter($plugins, function ($plugin) use ($allPlugins) {
        //     return $allPlugins[$plugin['handle']]['licenseKeyStatus'] !== self::LICENSE_STATUS_VALID;
        // });

        return $this->formatPluginData($plugins);
    }

    /**
     * Validate if settings is valid by request Craft id api
     *
     * @return boolean
     */
    public function validateSettingsWithApi() : bool
    {
        $result = $this->getLicenses(1, 1);
        return (!isset($result->message));
    }

    // Private Methods
    // =========================================================================

    /**
     * Format plugin data for twig template usage
     *
     * @param array $plugins
     * @return array
     */
    private function formatPluginData(array $plugins) : array
    {
        $allPlugins = Craft::$app->plugins->allPluginInfo;

        return array_map(function ($plugin) use ($allPlugins) {
            $plugin = (object) $plugin;

            $editions = array_map(function ($edition) {
                $edition = (object) $edition;
                return [
                    'name' => $edition->name,
                    'handle' => $edition->handle,
                    'isRenewal' => $edition->renewalPrice !== null
                ];
            }, $plugin->editions);

            $internalPlugin = $allPlugins[$plugin->handle];

            return [
                'name' => $plugin->name,
                'handle' => $plugin->handle,
                'version' => $plugin->version,
                'iconUrl' => $plugin->iconUrl,
                'shortDescription' => $plugin->shortDescription,
                'editions' => $editions,
                'needLicense' => $internalPlugin['licenseKeyStatus'] !== self::LICENSE_STATUS_VALID
            ];
        }, $plugins);
    }

    /**
     * Generate license with Craft id API
     *
     * @param string $email
     * @param string $pluginHandle
     * @return object
     */
    private function generateLicense(string $email, string $pluginHandle, string $editionHandle) : \stdClass
    {
        $options = [
            'edition' => $editionHandle,
            'plugin' => $pluginHandle,
            'email' => $email
          ];

        $url = $this->apiEndPoint . 'plugin-licenses';

        $result = $this->makeRequest($url, $options);

        return $result;
    }

    /**
    * Fetch licenses with Craft license API
    *
    * @param integer $page
    * @return array
    */
    private function getLicenses(int $page = 1, int $perPage = 100) : \stdClass
    {
        $params = "?plugin-licenses?page={$page}&perPage={$perPage}";
        $url = $this->apiEndPoint . 'plugin-licenses' . $params;
        $result = $this->makeRequest($url, [], 'GET');
        $this->validateRequest($result);
        return $result;
    }

    /**
     * Get the complete plugins list
     * Filter plugins with the same developerName as the settings (case-insensitive)
     *
     * @return array
     */
    private function getPluginsByDeveloperName() : array
    {
        $apiPluginsData = (object) Craft::$app->api->getPluginStoreData();
        $apiPlugins = $apiPluginsData->plugins;
        $developerName = $this->settings->developerName;
        $developerPlugins = array_values(array_filter($apiPlugins, function ($plugin) use ($developerName) {
            return strtolower($plugin['developerName']) === strtolower($developerName);
        }));
        return $developerPlugins;
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

    /**
     * Valid API call response
     *
     * @param [type] $response
     * @return void
     */
    private function validateRequest($response)
    {
        if (isset($response->message)) {
            Craft::$app->getSession()->set(self::SESSION_FLASH_KEY, $response->message);
        }
    }
}
