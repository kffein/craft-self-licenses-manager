<?php
/**
 * plugin-license-manager plugin for Craft CMS 3.x
 *
 * Plugin license manager
 *
 * @link      kffein.com
 * @copyright Copyright (c) 2018 Kffein
 */

namespace kffein\pluginlicensemanager\models;

use kffein\pluginlicensemanager\Pluginlicensemanager;
use craft\base\Model;

/**
 * @author    Kffein
 * @package   Pluginlicensemanager
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $username;
    public $apiKey;
    public $developerName;
    public $licenseEmail;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'apiKey', 'developerName'], 'string'],
            ['licenseEmail', 'email']
        ];
    }
}
