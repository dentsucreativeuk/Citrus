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

use \njpanderson\VarnishConnect;

/**
 * BanController Controller
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
class CitrusController extends Controller
{

    use \whitespace\citrus\helpers\BaseHelper;

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     * @access protected
     */
    protected $allowAnonymous = array('actionIndex');

    private $socket;

    /**
     * Handle a request going to our plugin's index action URL, e.g.: actions/controllersExample
     */
    public function actionIndex()
    {
        $bansSupported = Citrus::getInstance()->settings->bansSupported;

        $variables = $this->getTemplateStandardVars([
            'title' => '🍊 Citrus',
            'tabs' => [
                0 => [
                    'label' => 'Purge',
                    'url' => '#tab-purge'
                ]
            ],
            'bansSupported' => $bansSupported,
            'hosts' => $this->getVarnishHosts(),
            'adminHosts' => $this->getVarnishAdminHosts()
        ]);

        if ($bansSupported) {
            array_push($variables['tabs'], [
                'label' => 'Ban',
                'url' => '#tab-ban'
            ]);
        }

        if (Craft::$app->request->getBodyParam('purgeban_type')) {
            return $this->actionPurgeBan();
        }

        return $this->renderTemplate('citrus/index', $variables);
    }

    public function actionPurgeBan()
    {
        $type = Craft::$app->request->getBodyParam('purgeban_type');
        $query = Craft::$app->request->getBodyParam('query');
        $hostId = $this->getPostWithDefault('host', null);

        Craft::$app->response->headers->set('Content-type', 'application/json');

        if ($type === 'ban') {
            // Type is "ban" - send a ban query
            $responses = Citrus::getInstance()->citrus->banQuery($query, true, $hostId);
        } else {
            // Fall back to purge
            $responses = Citrus::getInstance()->citrus->purgeURI($query, $hostId);
        }

        if (Craft::$app->request->isAjax) {
            echo json_encode(array(
                'query' => $query,
                'responses' => ($responses),
                'CSRF' => array(
                    'name' => Craft::$app->config->general->csrfTokenName,
                    'value' => Craft::$app->request->getCsrfToken()
                )
            ));
        } else {
            Craft::$app->getSession()->setNotice(Craft::t('app','Cache cleared.'));

            $this->redirect('citrus');
        }
    }
}
