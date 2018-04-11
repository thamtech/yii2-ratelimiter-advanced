<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\limit;

use yii\base\BaseObject;

/**
 * DefaultRateLimitProvider is the default implementation of
 * [[RateLimitProviderInterface]]. It provides a convenient way to
 * define multipe rate limits in its object configuration array.
 *
 * For example,
 *
 * ```php
 * [
 *     'class' => 'thamtech\ratelimiter\limit\DefaultRateLimitProvider',
 *
 *     'definitions' => [
 *         'user' => 'app\models\User', // implements RateLimitInterface
 *         'ip' => [
 *             // 'class' defaults to 'thamtech\ratelimiter\limit\RateLimit'
 *             'limit' => 1000, // allowed hits per window
 *             'window' => 3600, // window in seconds
 *
 *             // Callable or anonymous function returning some unique
 *             // identifier. A separate allowance will be tracked for
 *             // each identifier.
 *             //
 *             // Leave unset to make such a rate apply globally
 *             // to all requests coming in through the controller
 *             'identifier' => function($context, $rateLimitId) {
 *                 return $context->request->getUserIP();
 *             }
 *         ],
 *
 *         'user-admin' => [
 *             'limit' => 1000,
 *             'window' => 3600,
 *
 *             'identifier' => Yii::$app->user->getIdentity()->id,
 *
 *             // make a rate limit only be considered under certain conditions
 *             'active' => Yii::$app->user->getIdentity()->isAdmin(),
 *         ],
 *     ],
 * ],
 * ```
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class DefaultRateLimitProvider extends BaseObject
{
    /**
     * @var array Rate Limit definitions
     *
     * This should be an associative array with Rate Limit IDs as keys
     * (you can make these up to keep track of distinct rate limits
     * you wish to apply).
     *
     * Values will be passed to [[Yii::createObject]] to instantiate
     * [[RateLimitInterface]] objects. If an array is specified without
     * a 'class' property, the 'thamtech\ratelimiter\limit\RateLimit'
     * class will be assumed.
     */
    public $definitions = [];

    /**
     * @inheritdoc
     */
    public function getRateLimits($context)
    {
        return $this->definitions;
    }
}
