<?php
/**
 * plugin-license-manager plugin for Craft CMS 3.x
 *
 * Plugin license manager
 *
 * @link      kffein.com
 * @copyright Copyright (c) 2018 Kffein
 */

namespace kffein\pluginlicensemanager\assetbundles\pluginlicensemanager;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Kffein
 * @package   Pluginlicensemanager
 * @since     1.0.0
 */
class PluginlicensemanagerAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@kffein/pluginlicensemanager/assetbundles/pluginlicensemanager/dist';

        $this->depends = [
            CpAsset::class,
        ];

        // $this->js = [
        //     'js/Pluginlicensemanager.js',
        // ];

        $this->css = [
            'css/Pluginlicensemanager.css',
        ];

        parent::init();
    }
}
