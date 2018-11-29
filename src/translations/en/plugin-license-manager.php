<?php
/**
 * plugin-license-manager plugin for Craft CMS 3.x
 *
 * Plugin license manager
 *
 * @link      kffein.com
 * @copyright Copyright (c) 2018 Kffein
 */

/**
 * @author    Kffein
 * @package   Pluginlicensemanager
 * @since     1.0.0
 */
return [
    'plugin-license-manager plugin loaded' => 'plugin-license-manager plugin loaded',

    'pluginName' => 'Plugin license manager',
    'index__title' => 'Generate and set license for plugins',
    'index__form__email__label' => 'Email',
    'index__form__email__desc' => 'Email associated with the license',
    'index__form__api__title' => 'Plugins detected by Craft ID api',
    'index__form__composer__title' => 'Plugins detected in composer.json for username : ',
    'index__form__composer__subtitle' => '
        The Craft ID api return only plugins that have at least one license generated for it.
        <br />
        The plugins list below, are plugins define in your composer.json file that match the username define in settings.
        <br />
        Please note that not all of plugins listed below can have a license generated.
    ',
    'index__form__button__label' => 'Generate',
    'index__form__licenseeditions' => 'Editions',
    'index__allpluginactivated' => 'All plugins activated',
    'index__errors__noplugingfound' => '
        <p>No plugins found for the developer name defined in the settings page.</p>
        <p>Please validate the settings informations</p>
        <p><a href="/admin/settings/plugins/plugin-license-manager" class="btn submit">Settings</a></p>
    ',
    'index__errors__invalidCredential' => '
        <p>The username and/or Api key is invalid.</p>
        <p>Please validate the plugin settings</p>
        <p><a href="/admin/settings/plugins/plugin-license-manager" class="btn submit">Settings</a></p>
    ',
    'index__form__licensestatusvalid' => 'License status is valid',
    'errors__email' => 'Invalid email address. Please submit a valid email address.',
    'errors__nopluginsselected' => 'No plugins selected. Please select at least one plugin.',
    'errors__forplugins' => 'Errors for plugin license : ',
    'errors__nopluginsapifound' => 'No plugins found for the developer name defined in the settings page. Please validate settings.',
    'success__forplugins' => 'Success for plugin license : ',
    'settings__username__label' => 'Username',
    'settings__username__instructions' => 'Username for the Craft id API',
    'settings__apikey__label' => 'Api key',
    'settings__apikey__instructions' => 'Token for the Craft id API',
    'settings__developername__label' => 'Developer name',
    'settings__developername__instructions' => 'Developer name appearing in the Developer > Profile section of the Craft id API panel',
    'settings__email__label' => 'License email',
    'settings__email__instructions' => 'Email used for generating license',
];
