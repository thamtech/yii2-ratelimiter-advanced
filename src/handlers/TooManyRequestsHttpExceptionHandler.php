<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\handlers;

use yii\web\TooManyRequestsHttpException;

/**
 * TooManyRequestsHttpExceptionHandler is used to throw a
 * [[TooManyHttpRequestsHttpException]] whenever rate limits are
 * exceeded.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class TooManyRequestsHttpExceptionHandler extends BaseHandler
{
    /**
     * @var string The message to include in the [[TooManyHttpRequestsHttpException]]
     */
    public $message = 'Rate limit exceeded.';

    /**
     * @inheritdoc
     */
    public function onRateLimitsExceeded($event)
    {
        throw new TooManyRequestsHttpException($this->message);
    }
}
