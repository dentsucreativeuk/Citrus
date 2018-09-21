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
use craft\db\Query;

use whitespace\citrus\records\BindingsRecord;

/**
 * BindingsService Service
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
class BindingsService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the active BindingsRecord bindings for a section, grouped by type
     */
    public function getBindings(int $sectionId, int $typeId = 0, $bindType = '')
    {
        $attrs = [
            'sectionId' => $sectionId
        ];

        if ($typeId !== 0) {
            $attrs['typeId'] = $typeId;
        }

        if (!empty($bindType)) {
            $attrs['bindType'] = $bindType;
        }

        return BindingsRecord::findAll($attrs);
    }

    /**
     * Returns the current CMS sections with binding counts.
     */
    public function getSections()
    {
        $result = [];

        $sections = Craft::$app->sections->getAllSections();
        $bindings = $this->getBindingCounts();

        foreach ($sections as $section) {
            $result[] = array(
                'bindings' => isset($bindings[$section->id]) ? $bindings[$section->id] : 0,
                'craftSection' => $section
            );
        }

        return $result;
    }

    /**
     * Returns the binding counts, grouped by section.
     */
    public function getBindingCounts()
    {
        $result = [];

        $sections = (new Query())
            ->select('sectionId, count(*) AS num')
            ->from(BindingsRecord::tableName())
            ->groupBy(['sectionId'])
            ->all();

        foreach ($sections as $section) {
            $result[$section['sectionId']] = $section['num'];
        }

        return $result;
    }

    /**
     * Clears the current bindings for a section.
     */
    public function clearBindings(int $sectionId)
    {
        BindingsRecord::deleteAll(
            'sectionId = ' . $sectionId
        );

        return true;
    }

    /**
     * (Re)sets the active bindings for a section.
     */
    public function setBindings(int $sectionId, array $data = array())
    {
        $success = true;

        foreach ($data as $entryType => $bindings) {
            foreach ($bindings as $binding) {
                $record = new BindingsRecord;
                $record->sectionId = $sectionId;
                $record->typeId = $entryType;
                $record->bindType = $binding['bindType'];
                $record->query = $binding['query'];
                $success = $record->save();

                if (!$success) {
                    // early return if a save failed
                    return $success;
                }
            }
        }

        return $success;
    }
}
