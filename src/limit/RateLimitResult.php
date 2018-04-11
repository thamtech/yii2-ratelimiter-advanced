<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\limit;

use yii\base\BaseObject;

/**
 * RateLimitResult represents the result of a rate limit check along with a
 * reference to the original RateLimit object.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class RateLimitResult extends BaseObject
{
    /**
     * @var int unix timestamp at the time the rate limit was checked
     */
    public $timestamp;

    /**
     * @var int allowance after rate was checked
     */
    public $allowance;

    /**
     * @var bool whether or not the rate limit is exceeded
     */
    public $isExceeded;

    /**
     * @var string the ID of the defined rate limit
     */
    public $rateLimitId;

    /**
     * @var RateLimit the original RateLimit object
     */
    public $rateLimit;
}
