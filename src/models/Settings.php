<?php
/**
 * citrus plugin for Craft CMS 3.x
 *
 * Automatically purge and ban cached elements in Varnish
 *
 * @link      https://whitespacers.com
 * @copyright Copyright (c) 2018 Whitespace
 */

namespace whitespace\citrus\models;

use whitespace\citrus\Citrus;

use Craft;
use craft\base\Model;

/**
 * Citrus Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Whitespace
 * @package   Citrus
 * @since     0.0.1
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Some field model attribute
     *
     * @var string
     */
    public $varnishHosts = [];
    public $purgeEnabled = false;
    public $purgeRelated = true;
    public $logAll = 0;
    public $purgeUriMap = [];
    public $bansSupported = false;
    public $banQueryHeader = 'Ban-Query-Full';
    public $adminCookieName = 'CitrusAdmin';
}
