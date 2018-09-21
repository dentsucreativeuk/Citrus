<?php
/**
 * Citrus plugin for Craft CMS 3.x
 *
 * Automatically purge and ban elements in Varnish.
 *
 * @link      https://github.com/njpanderson/Citrus
 * @copyright Copyright (c) 2018 Neil Anderson
 */

/**
 * Citrus config.php
 *
 * This file exists only as a template for the Citrus settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'citrus.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [

    // This controls blah blah blah
    "varnishHosts" => [
        'public' => [
            'url' => '',
            'hostName' => '',
            'adminIP' => '',
            'adminPort' => '',
            'adminSecret' => ''
        ]
    ],
    'purgeEnabled' => '',
    'purgeRelated' => '',
    'logAll' => '',
    'purgeUriMap' => '',
    'bansSupported' => '',
    'banQueryHeader' => '',
    'adminCookieName' => ''
];
