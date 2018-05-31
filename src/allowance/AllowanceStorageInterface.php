<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
 **/

namespace thamtech\ratelimiter\allowance;

/**
 * AllowanceStorageInterface is the interface that may be implemented to provide
 * storage for rate limit allowance data.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
interface AllowanceStorageInterface
{
    /**
     * Loads the number of allowed requests and the corresponding timestamp from
     * a persistent storage.
     *
     * @param string $id an identifier used to scope the rate limit
     *
     * @param \thamtech\ratelimiter\Context $context the current request/action
     *     context
     *
     * @return array an array of two elements. The first element is the number
     *     of allowed requests, and the second element is the corresponding UNIX
     *     timestamp.
     *
     * Signature and docs borrowed from [[\yii\filters\RateLimitInterface]],
     * @author Qiang Xue <qiang.xue@gmail.com>
     */
    public function loadAllowance($id, $context);

    /**
     * Saves the number of allowed requests and the corresponding timestamp to
     * a persistent storage.
     *
     * @param string $id an identifier used to scope the rate limit
     *
     * @param \thamtech\ratelimiter\Context $context the current request/action
     *     context
     *
     * @param int $allowance the number of allowed requests remaining.
     *
     * @param int $timestamp the current timestamp.
     *
     * @param int $window the maximum duration which the allowance needs to be
     *     stored in seconds.
     *
     * Signature and docs borrowed from [[\yii\filters\RateLimitInterface]],
     * @author Qiang Xue <qiang.xue@gmail.com>
     */
    public function saveAllowance($id, $context, $allowance, $timestamp, $window);
}
