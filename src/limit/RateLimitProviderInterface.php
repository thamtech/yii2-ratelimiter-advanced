<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\limit;

/**
 * RateLimitProviderInterface is the interface that may be implemented to
 * provide rate limit definitions.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
interface RateLimitProviderInterface
{
    /**
     * Gets an array of [[RateLimit]]s or [[RateLimit]] configuration arrays
     * keyed on rate limit IDs.
     *
     * @param \thamtech\ratelimiter\Context $context the current request/action
     *     context
     *
     * @return array associative array of rate limit IDs mapped to either
     *     [[RateLimit]] objects or [[RateLimit]] configuration arrays.
     */
    public function getRateLimits($context);
}
