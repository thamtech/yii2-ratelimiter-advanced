<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\handlers;

use Yii;

/**
 * RateLimitHeadersHandler is used to apply X-Rate-Limit-* or similar
 * HTTP headers when rate limits are checked.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class RateLimitHeadersHandler extends BaseHeadersHandler
{
    /**
     * @var string|string[] A single string prefix, or an array of strings to
     *     duplicate the headers with multiple prefixes.
     */
    public $prefix = 'X-Rate-Limit-';

    /**
     * @inheritdoc
     */
    public function onRateLimitsChecked($event)
    {
        $headers = $this->getResponse()->getHeaders();

        $values = $this->computeHeaderValues($event->rateLimitResults);

        $prefixes = $this->prefix;
        if (!is_array($prefixes)) {
            $prefixes = [$prefixes];
        }

        foreach ($prefixes as $prefix) {
            $headers
              ->set($prefix . 'Limit', $values['limit'])
              ->set($prefix . 'Remaining', $values['remaining'])
              ->set($prefix . 'Reset', $values['reset']);
        }
    }
}
