<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter;

use yii\base\Event;

/**
 * This event class is used for rateLimitChecked Events triggered by the
 * [[RateLimiter]] class.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class RateLimitsCheckedEvent extends Event
{
    /**
     * @var  \thamtech\ratelimiter\Context the current request/action
     *     context
     */
    public $context;

    /**
     * @var int the current unix timestamp
     */
    public $time;

    /**
     * @var array associative array keyed on rate limit IDs with the
     * [[RateLimitResult]]s of checked rate limits as values.
     */
    public $rateLimitResults;
}
