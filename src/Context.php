<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter;

use yii\base\BaseObject;

/**
 * A Context object olds the [[\yii\web\Request]] and [[\yii\base\Action]] in
 * which the current rate limits are being checked.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class Context extends BaseObject
{
    /**
     * @var \yii\web\Request the current request
     */
    public $request;

    /**
     * @var \yii\base\Action the action to be executed
     */
    public $action;
}
