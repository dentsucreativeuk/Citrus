<?php
/**
 * citrus plugin for Craft CMS 3.x
 *
 * Automatically purge and ban cached elements in Varnish
 *
 * @link      https://whitespacers.com
 * @copyright Copyright (c) 2018 Whitespace
 */

namespace whitespace\citrus\migrations;

use whitespace\citrus\Citrus;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * citrus Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    Whitespace
 * @package   Citrus
 * @since     0.0.1
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

    // citrus_bindings table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%citrus_bindings}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%citrus_bindings}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                // Custom columns in the table
                    'sectionId' => $this->integer()->notNull(),
                    'typeId' => $this->integer()->notNull(),
                    'query' => $this->string(255)->notNull()->defaultValue(''),
                    'bindType' => "ENUM('PURGE', 'BAN', 'FULBAN')"
                ]
            );
        }

    // citrus_entry table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%citrus_entry}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%citrus_entry}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                // Custom columns in the table
                    'uriId' => $this->integer()->notNull(),
                    'entryId' => $this->integer()->notNull(),
                ]
            );
        }

    // citrus_uri table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%citrus_uri}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%citrus_uri}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                // Custom columns in the table
                    'uriHash' => $this->text()->notNull(),
                    'locale' => $this->string(255)->notNull()->defaultValue(''),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {
    // citrus_bindings table
        $this->createIndex(
            $this->db->getIndexName(
                '{{%citrus_bindings}}',
                'sectionId',
                true
            ),
            '{{%citrus_bindings}}',
            'sectionId'
        );
        $this->createIndex(
            $this->db->getIndexName(
                '{{%citrus_bindings}}',
                'typeId',
                true
            ),
            '{{%citrus_bindings}}',
            'typeId'
        );
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }

    // citrus_entry table
        $this->createIndex(
            $this->db->getIndexName(
                '{{%citrus_entry}}',
                'uriId',
                true
            ),
            '{{%citrus_entry}}',
            'uriId',
            true
        );
        $this->createIndex(
            $this->db->getIndexName(
                '{{%citrus_entry}}',
                'entryId',
                true
            ),
            '{{%citrus_entry}}',
            'entryId',
            true
        );
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }

    // citrus_uri table
        $this->createIndex(
            $this->db->getIndexName(
                '{{%citrus_uri}}',
                'uriHash',
                true
            ),
            '{{%citrus_uri}}',
            'uriHash'
        );
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {

    // citrus_entry table
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%citrus_entry}}', 'uriId'),
            '{{%citrus_entry}}',
            'uriId',
            '{{%citrus_uri}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
    // citrus_bindings table
        $this->dropTableIfExists('{{%citrus_bindings}}');

    // citrus_entry table
        $this->dropTableIfExists('{{%citrus_entry}}');

    // citrus_uri table
        $this->dropTableIfExists('{{%citrus_uri}}');
    }
}
