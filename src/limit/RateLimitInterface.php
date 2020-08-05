<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\limit;

/**
 * RateLimitInterface is the interface that may be implemented to provide
 * rate limit parameters.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
interface RateLimitInterface
{
    /**
     * Returns the maximum number of allowed requests and the window size.
     *
     * Implementers of this function must either return a [[RateLimit]] object
     * or an array of [[RateLimit]] properties. For example,
     *
     * ```php
     * return [
     *     'limit' => 100,
     *     'window' => 3600,
     *     'identifier' => Yii::$app->user->getIdentity()->getId(),
     * ];
     * ```
     *
     * @param \thamtech\ratelimiter\Context $context the current request/action
     *     context
     *
     * @return RateLimit|array A [[RateLimit]] object or an array of
     *     [[RateLimit]] properties.
     *
     *
     * Signature and docs borrowed from [[\yii\filters\RateLimitInterface]],
     * @author Qiang Xue <qiang.xue@gmail.com>
     */
    public function getRateLimit($context);

    /**
     * Indicates whether or not this rate limit should be checked.
     *
     * @param \thamtech\ratelimiter\Context $context the current request/action
     *     context
     *
     * @param string $rateLimitId The array key that defined the rate limit
     *    in the [[RateLimiter]].
     *
     * @return bool [description]
     */
    public function isActive($context, $rateLimitId);
}
