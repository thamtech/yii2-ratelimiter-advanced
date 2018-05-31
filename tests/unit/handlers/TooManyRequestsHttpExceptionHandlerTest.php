<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace tests\unit\handlers;

use tests\unit\TestCase;
use Codeception\Specify;
use thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler;
use thamtech\ratelimiter\RateLimitsExceededEvent;

/**
 * TooManyRequestsHttpExceptionHandler test
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class TooManyRequestsHttpExceptionHandlerTest extends TestCase
{
    use Specify;

    /**
     * @expectedException yii\web\TooManyRequestsHttpException
     * @expectedExceptionMessage Rate limit exceeded.
     */
    public function testOnRateLimitsExceededDefault()
    {
        $handler = new TooManyRequestsHttpExceptionHandler();
        $handler->onRateLimitsExceeded(null);
    }

    /**
     * @expectedException yii\web\TooManyRequestsHttpException
     * @expectedExceptionMessage abc
     */
    public function testOnRateLimitsExceededCustomMessage()
    {
        $handler = new TooManyRequestsHttpExceptionHandler([
            'message' => 'abc',
        ]);
        $handler->onRateLimitsExceeded(null);
    }
}
