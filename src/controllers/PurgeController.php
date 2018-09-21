<?php
/**
 * citrus plugin for Craft CMS 3.x
 *
 * Automatically purge and ban cached elements in Varnish
 *
 * @link      https://whitespacers.com
 * @copyright Copyright (c) 2018 Whitespace
 */

namespace whitespace\citrus\controllers;

use whitespace\citrus\Citrus;

use Craft;
use craft\web\Controller;

/**
 * PurgeController Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Whitespace
 * @package   Citrus
 * @since     0.0.1
 */
class PurgeController extends Controller
{

    use \whitespace\citrus\helpers\BaseHelper;

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['test'];

    // Public Methods
    // =========================================================================

    private $elementId;
    private $numUris;

    public function init()
    {
        $this->elementId = (int) Craft::$app->request->getQueryParam('id');
        $this->numUris = (int) Craft::$app->request->getQueryParam('n', 10);
    }

    public function actionTest()
    {
        if ($this->elementId) {
            $this->testElementId($this->elementId);
        } else {
            $this->testUris($this->numUris);
        }
    }

    private function testElementId($id)
    {
        $element = Craft::$app->elements->getElementById($id);

        echo "Purging element \"{$element->title}\" ({$element->id})<br/>\r\n";

        $tasks = Craft::$app->citrus->purgeElement($element, true, true);

        foreach ($tasks as $task) {
            Craft::$app->tasks->runTask($task);
        }
    }

    private function testUris($num)
    {
        $settings = array(
            'uris' => $this->fillUris(
                '',
                $num
            ),
            'debug' => true
        );

        $task = Craft::$app->tasks->createTask('Citrus_Purge', null, $settings);
        Craft::$app->tasks->runTask($task);
    }

    private function fillUris($prefix, int $count = 1) {
        $result = array();

        for ($a = 0; $a < $count; $a += 1) {
            array_push(
                $result,
                Craft::$app->citrus->makeVarnishUri($prefix . '?n=' . $this->uuid())
            );
        }

        return $result;
    }
}
