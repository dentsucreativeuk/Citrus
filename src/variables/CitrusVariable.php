<?php
/**
 * citrus plugin for Craft CMS 3.x
 *
 * Automatically purge and ban cached elements in Varnish
 *
 * @link      https://whitespacers.com
 * @copyright Copyright (c) 2018 Whitespace
 */

namespace whitespace\citrus\variables;

use whitespace\citrus\Citrus;

use Craft;

/**
 * citrus Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.citrus }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Whitespace
 * @package   Citrus
 * @since     0.0.1
 */
class CitrusVariable
{
    /**
     * Gets the client IP, accounting for request being routed through Varnish (HTTP_X_FORWARDED_FOR header set)
     *
     * @return string
     */
    public function clientip()
    {
        $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

        // X-forwarded-for could be a comma-delimited list of all ip's the request was routed through.
        // The last ip in the list is expected to be the users originating ip.
        if (strpos($ip, ',') !== false) {
            $arr = explode(',', $ip);
            $ip = trim(array_pop($arr), " ");
        }

        return $ip;
    }
}
