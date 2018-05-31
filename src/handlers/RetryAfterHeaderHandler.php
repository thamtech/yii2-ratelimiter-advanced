<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\handlers;

use Yii;

/**
 * RetryAfterHeaderHandler is used to apply a Retry-After
 * HTTP header when rate limits are exceeded.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class RetryAfterHeaderHandler extends BaseHeadersHandler
{
    /**
     * @var string|string[] A single string prefix, or an array of strings to
     *     duplicate the headers with multiple prefixes.
     */
    public $header = 'Retry-After';

    /**
     * @inheritdoc
     */
    public function onRateLimitsExceeded($event)
    {
        $headers = $this->getResponse()->getHeaders();
        $values = $this->computeHeaderValues($event->rateLimitResults);
        $retryAfter = $values['retryAfter'];

        if ($retryAfter > 0) {
            $headers->set($this->header, $values['retryAfter']);
        }
    }
}
