<?php
/**
 * plugin-license-manager plugin for Craft CMS 3.x
 *
 * Plugin license manager
 *
 * @link      kffein.com
 * @copyright Copyright (c) 2018 Kffein
 */

namespace kffein\pluginlicensemanager;

use kffein\pluginlicensemanager\services\PluginlicensemanagerService as PluginlicensemanagerServiceService;
use kffein\pluginlicensemanager\variables\PluginlicensemanagerVariable;
use kffein\pluginlicensemanager\models\Settings;
use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;
use yii\base\Event;

/**
 * Class Pluginlicensemanager
 *
 * @author    Kffein
 * @package   Pluginlicensemanager
 * @since     1.0.0
 *
 * @property  PluginlicensemanagerServiceService $pluginlicensemanagerService
 */
class Pluginlicensemanager extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Pluginlicensemanager
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Quit if request is not a CP request
        if (!Craft::$app->request->isCpRequest) {
            return null;
        }

        parent::init();
        self::$plugin = $this;

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'plugin-license-manager/default';
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('pluginlicensemanager', PluginlicensemanagerVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Event::on(
            Plugin::class,
            Plugin::EVENT_BEFORE_SAVE_SETTINGS,
            function (Event $event) {
                $isValid = Pluginlicensemanager::getInstance()->pluginlicensemanagerService->validateSettingsWithApi();
                if (!$isValid) {
                    Craft::$app->getSession()->setError('API validation fail. Invalid settings informations');
                    Craft::$app->getResponse()->redirect('/admin/settings/plugins/plugin-license-manager')->send();
                }
                Craft::$app->getResponse()->redirect('/admin/plugin-license-manager')->send();
                return true;
            }
        );

        Craft::info(
            Craft::t(
                'plugin-license-manager',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'plugin-license-manager/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
