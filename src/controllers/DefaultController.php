<?php
/**
 * plugin-license-manager plugin for Craft CMS 3.x
 *
 * Plugin license manager
 *
 * @link      kffein.com
 * @copyright Copyright (c) 2018 Kffein
 */

namespace kffein\pluginlicensemanager\controllers;

use kffein\pluginlicensemanager\Pluginlicensemanager;
use craft\web\Controller;
use Craft;

/**
 * @author    Kffein
 * @package   Pluginlicensemanager
 * @since     1.0.0
 */
class DefaultController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['generate'];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionGenerate()
    {
        $pluginsHandles = [];
        $plugins = Craft::$app->request->getRequiredBodyParam('pluginsHandle');
        $plugins = array_filter($plugins);
        $editions = Craft::$app->request->getRequiredBodyParam('edition');

        $success = [];
        $errors = [];

        if (empty($plugins)) {
            Craft::$app->getSession()->setError(Craft::t('plugin-license-manager', 'errors__nopluginsselected'));
            return $this->renderTemplate('plugin-license-manager/index');
        }

        foreach ($plugins as $pluginHandle => $plugin) {
            $pluginsHandles[] = $pluginHandle;
        }

        foreach ($pluginsHandles as $pluginHandle) {
            $editionHandle = $editions[$pluginHandle];

            if (Pluginlicensemanager::getInstance()->pluginlicensemanagerService->generateAndActivatePluginLicense($pluginHandle, $editionHandle)) {
                $success[] = $pluginHandle;
            } else {
                $errors[] = $pluginHandle;
            }
        }

        if ($success) {
            $successMsg = Craft::t('plugin-license-manager', 'success__forplugins') . implode(', ', $success);
            Craft::$app->getSession()->setNotice($successMsg);
        }

        if (!empty($errors)) {
            $errorMsg = Craft::t('plugin-license-manager', 'errors__forplugins') . implode(', ', $errors);
            Craft::$app->getSession()->setError($errorMsg);
        }

        return $this->redirectToPostedUrl();
    }
}
