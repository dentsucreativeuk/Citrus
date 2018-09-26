<?php
/**
 * citrus plugin for Craft CMS 3.x
 *
 * Automatically purge and ban cached elements in Varnish
 *
 * @link      https://whitespacers.com
 * @copyright Copyright (c) 2018 Whitespace
 */

namespace whitespace\citrus\jobs;

use whitespace\citrus\Citrus;

use Craft;
use craft\queue\BaseJob;

use whitespace\citrus\helpers\PurgeHelper;

/**
 * PurgeJob job
 *
 * Jobs are run in separate process via a Queue of pending jobs. This allows
 * you to spin lengthy processing off into a separate PHP process that does not
 * block the main process.
 *
 * You can use it like this:
 *
 * use whitespace\citrus\jobs\PurgeJob as PurgeJobJob;
 *
 * $queue = Craft::$app->getQueue();
 * $jobId = $queue->push(new PurgeJobJob([
 *     'description' => Craft::t('citrus', 'This overrides the default description'),
 *     'someAttribute' => 'someValue',
 * ]));
 *
 * The key/value pairs that you pass in to the job will set the public properties
 * for that object. Thus whatever you set 'someAttribute' to will cause the
 * public property $someAttribute to be set in the job.
 *
 * Passing in 'description' is optional, and only if you want to override the default
 * description.
 *
 * More info: https://github.com/yiisoft/yii2-queue
 *
 * @author    Whitespace
 * @package   Citrus
 * @since     0.0.1
 */
class PurgeJob extends BaseJob
{
    public $uris;
    public $debug;
    public $purge;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     * You don't need any steps or any other special logic handling, just do the
     * jobs that needs to be done here.
     *
     * More info: https://github.com/yiisoft/yii2-queue
     */
    public function execute($queue)
    {
        $this->purge = new PurgeHelper();
        // $this->uris = $this->getSettings()->uris;
        // $this->debug = $this->getSettings()->debug;

        $totalSteps = count($this->uris);
        for ($step = 0; $step < $totalSteps; $step++)
        {
            $this->setProgress($queue, $step / $totalSteps);

            $this->purge->purge(
                $this->uris[$step],
                $this->debug
            );

            // Sleep for .1 seconds
            usleep(100000);
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function defaultDescription(): string
    {
        return Craft::t('citrus', 'Purging Varnish cache');
    }
}
