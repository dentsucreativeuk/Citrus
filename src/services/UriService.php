<?php
/**
 * citrus plugin for Craft CMS 3.x
 *
 * Automatically purge and ban cached elements in Varnish
 *
 * @link      https://whitespacers.com
 * @copyright Copyright (c) 2018 Whitespace
 */

namespace whitespace\citrus\services;

use whitespace\citrus\Citrus;

use Craft;
use craft\base\Component;
use whitespace\citrus\records\EntryRecord;
use whitespace\citrus\records\UriRecord;

/**
 * UriService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Whitespace
 * @package   Citrus
 * @since     0.0.1
 */
class UriService extends Component
{
    use \whitespace\citrus\helpers\BaseHelper;

    // Public Methods
    // =========================================================================

    public function saveURIEntry(string $pageUri, int $entryId, string $locale)
    {
        $uriHash = $this->hash($pageUri);

        // Save URI record
        $uri = $this->getURIByURIHash(
            $uriHash
        );

        $uri->uri = $pageUri;
        $uri->uriHash = $uriHash;
        $uri->locale = (!empty($locale) ? $locale : null);

        $this->saveURI($uri);

        // Save Entry record
        $entry = new EntryRecord();

        $entry->uriId = $uri->id;
        $entry->entryId = $entryId;

        Craft::$app->citrus_entry->saveEntry($entry);
    }

    public function deleteURI(string $pageUri)
    {
        $uriHash = $this->hash($pageUri);

        // Save URI record
        $uri = $this->getURIByURIHash(
            $uriHash
        );

        if (!$uri->isNewRecord) {
            $uri->delete();
        }
    }

    public function getURI($id)
    {
        return UriRecord::model()->findAllByPk($id);
    }

    public function getURIByURIHash($uriHash = '')
    {
        if (empty($uriHash)) {
            throw new Exception('$uriHash cannot be blank.');
        }

        $uri = UriRecord::model()->findByAttributes(array(
          'uriHash' => $uriHash
        ));

        if ($uri !== null) {
            return $uri;
        }

        return new UriRecord();
    }

    public function getAllURIsByEntryId(int $entryId)
    {
        return UriRecord::find()->with(array(
            'entries' => array(
                'select' => false,
                'condition' => 'entryId = ' . $entryId
            )
        ))->all();
    }

    public function saveURI(
        UriRecord $uri
    ) {
        $uri->save();
    }
}
