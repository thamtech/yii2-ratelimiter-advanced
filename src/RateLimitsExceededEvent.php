<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter;

/**
 * This event class is used for rateLimitExceeded Events triggered by the
 * [[RateLimiter]] class.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class RateLimitsExceededEvent extends RateLimitsCheckedEvent
{

    /**
     * @var bool indication of whether the request is valid. This defaults to
     * true but any handler of this event may set it to false if it would like
     * the [[RateLimiter]::beforeAction] to return false and prevent the
     * request from continuing.
     */
    public $isValid = true;
}
