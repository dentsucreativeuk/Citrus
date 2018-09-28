<?php
/**
 * citrus plugin for Craft CMS 3.x
 *
 * Automatically purge and ban cached elements in Varnish
 *
 * @link      https://whitespacers.com
 * @copyright Copyright (c) 2018 Whitespace
 */

namespace whitespace\citrus;

use whitespace\citrus\services\BindingsService;
use whitespace\citrus\services\EntryService;
use whitespace\citrus\services\UriService;
use whitespace\citrus\services\CitrusService;
use whitespace\citrus\variables\CitrusVariable;
use whitespace\citrus\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\User;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;
use craft\services\Elements;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Whitespace
 * @package   Citrus
 * @since     0.0.1
 *
 * @property  BindingsService $bindingsService
 * @property  EntryService $entryService
 * @property  UriService $uriService
 * @property  CitrusService $citrusService
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class Citrus extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Citrus::$plugin
     *
     * @var Citrus
     */
    public static $plugin;

    const URI_TAG = 0;
    const URI_ELEMENT = 1;
    const URI_BINDING = 2;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '0.0.2';

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Citrus::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        require __DIR__ . '/vendor/autoload.php';

        // Set components
        $this->setComponents([
            'bindings' => BindingsService::class,
            'entry' => EntryService::class,
            'uri' => UriService::class,
            'citrus' => CitrusService::class,
        ]);

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['citrus'] = 'citrus/citrus/index';
                $event->rules['citrus/pages'] = 'citrus/pages/index';
                $event->rules['citrus/bindings'] = 'citrus/bindings/index';
                $event->rules['citrus/bindings/section'] = 'citrus/bindings/section';
                $event->rules['citrus/ban'] = 'citrus/pages/index';
                $event->rules['citrus/ban/list'] = 'citrus/ban/list';
                $event->rules['citrus/test/purge'] = 'citrus/purge/test';
                $event->rules['citrus/test/ban'] = 'citrus/ban/test';
                $event->rules['citrus/test/bindings'] = 'citrus/bindings/test';
            }
        );

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('citrus', CitrusVariable::class);
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );

        if ($this->settings->purgeEnabled) {
            $purgeRelated = $this->settings->purgeRelated;

            Event::on(
                Elements::class,
                Elements::EVENT_AFTER_SAVE_ELEMENT,
                function (Event $event) use ($purgeRelated) {
                    // element saved
                    Citrus::getInstance()->citrus->purgeElement($event->element, $purgeRelated);
                }
            );

            Event::on(
                Elements::class,
                Elements::EVENT_AFTER_DELETE_ELEMENT,
                function (Event $event) use ($purgeRelated) {
                    // element deleted
                    Citrus::getInstance()->citrus->purgeElement($event->element, $purgeRelated);
                }
            );

            Event::on(
                Elements::class,
                Elements::EVENT_AFTER_PERFORM_ACTION,
                function (Event $event) use ($purgeRelated) {
                    //entry deleted via element action
                    $action = $event->params['action']->classHandle;
                    if ($action == 'Delete') {
                        $elements = $event->params['criteria']->find();

                        foreach ($elements as $element) {
                            if ($element->elementType !== 'Entry') {
                                return;
                            }

                            Citrus::getInstance()->citrus->purgeElement($element, $purgeRelated);
                        }
                    }
                }
            );
        }

        // Add/Remove citrus cookies
        Event::on(
            User::class,
            User::EVENT_AFTER_LOGIN,
            function (Event $event) {
                $this->setCitrusCookie('1');
            }
        );
        Event::on(
            User::class,
            User::EVENT_AFTER_LOGOUT,
            function (Event $event) {
                $this->setCitrusCookie();
            }
        );

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'citrus',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function getCpNavItem()
    {
        $item = parent::getCpNavItem();
        $item['subnav'] = [
            'bindings' => ['label' => 'Bindings', 'url' => 'citrus/bindings'],
        ];
        return $item;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'citrus/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    public static function log(
        $message,
        $level = 'info',
        $override = false,
        $debug = false
    ) {
        if ($debug) {
            // Also write to screen
            if ($level === 'error') {
                echo '<span style="color: red; font-weight: bold;">' . $message . "</span><br/>\n";
            } else {
                echo $message . "<br/>\n";
            }
        }

        Craft::getLogger()->log($message, $level, $category = 'Citrus');
    }

    private function setCitrusCookie($value = '')
    {
        $cookieName = $this->settings->adminCookieName;

        if ($cookieName === false) {
            return;
        }

        setcookie(
            $cookieName,
            $value,
            0,
            '/',
            null,
            Craft::$app->request->getIsSecureConnection(),
            true
        );
    }
}
