<?php
/**
 * @copyright Copyright (c) 2016-2018 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-ratelimiter-advanced
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace tests\unit\handlers;

use tests\unit\TestCase;
use Codeception\Specify;
use thamtech\ratelimiter\handlers\BaseHandler;
use thamtech\ratelimiter\RateLimitsExceededEvent;
use thamtech\ratelimiter\RateLimitsCheckedEvent;

/**
 * BaseHandler test
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class BaseHandlerTest extends TestCase
{
    use Specify;

    public function testExceededHandler()
    {
        $handler = new TestableBaseHandler([
            'only' => ['user'],
        ]);
        $event = new RateLimitsExceededEvent([
            'rateLimitResults' => [
                'ip' => true, // placeholder
            ],
        ]);

        $handler->exceededHandler($event);
    }

    public function testCheckedHandler()
    {
        $handler = new TestableBaseHandler([
            'only' => ['user'],
        ]);
        $event = new RateLimitsCheckedEvent([
            'rateLimitResults' => [
                'ip' => true, // placeholder
            ],
        ]);

        $handler->checkedHandler($event);
    }

    public function testOnRateLimitsExceeded()
    {
        $handler = new TestableBaseHandler();
        $event = new RateLimitsExceededEvent();

        $handler->onRateLimitsExceeded($event);
        $this->assertFalse($event->handled);
    }

    public function testOnRateLimitsChecked()
    {
        $handler = new TestableBaseHandler();
        $event = new RateLimitsCheckedEvent();

        $handler->onRateLimitsChecked($event);
        $this->assertFalse($event->handled);
    }

    public function testIsActiveDefault()
    {
        $handler = new TestableBaseHandler();

        // no rateLimitIds is not a valid event; handler should
        // not be active
        $this->assertFalse($handler->isActive([]));

        $this->assertTrue($handler->isActive(['user']));
        $this->assertTrue($handler->isActive(['user', 'ip']));
        $this->assertTrue($handler->isActive(['ip']));
    }

    public function testIsActiveOnlyOne()
    {
        $handler = new TestableBaseHandler([
            'only' => ['user'],
        ]);

        // no rateLimitIds is not a valid event; handler should
        // not be active
        $this->assertFalse($handler->isActive([]));

        $this->assertTrue($handler->isActive(['user']));
        $this->assertTrue($handler->isActive(['user', 'ip']));
        $this->assertFalse($handler->isActive(['ip']));
    }

    public function testIsActiveOnlyBoth()
    {
        $handler = new TestableBaseHandler([
            'only' => ['user', 'ip'],
        ]);

        // no rateLimitIds is not a valid event; handler should
        // not be active
        $this->assertFalse($handler->isActive([]));

        $this->assertTrue($handler->isActive(['user']));
        $this->assertTrue($handler->isActive(['user', 'ip']));
        $this->assertTrue($handler->isActive(['ip']));
    }

    public function testIsActiveExcept()
    {
        $handler = new TestableBaseHandler([
            'except' => ['user'],
        ]);

        // no rateLimitIds is not a valid event; handler should
        // not be active
        $this->assertFalse($handler->isActive([]));

        $this->assertFalse($handler->isActive(['user']));
        $this->assertTrue($handler->isActive(['user', 'ip']));
        $this->assertTrue($handler->isActive(['ip']));
    }

    public function testIsActiveExceptBoth()
    {
        $handler = new TestableBaseHandler([
            'except' => ['user', 'ip'],
        ]);

        // no rateLimitIds is not a valid event; handler should
        // not be active
        $this->assertFalse($handler->isActive([]));

        $this->assertFalse($handler->isActive(['user']));
        $this->assertFalse($handler->isActive(['user', 'ip']));
        $this->assertFalse($handler->isActive(['ip']));
    }

    public function testIsActiveOnlyExcept()
    {
        $handler = new TestableBaseHandler([
            'only' => ['user'],
            'except' => ['user'],
        ]);

        // no rateLimitIds is not a valid event; handler should
        // not be active
        $this->assertFalse($handler->isActive([]));

        $this->assertFalse($handler->isActive(['user']));
        $this->assertFalse($handler->isActive(['user', 'ip']));
        $this->assertFalse($handler->isActive(['ip']));
    }

    public function testIsActiveOnlyBothExcept()
    {
        $handler = new TestableBaseHandler([
            'only' => ['user', 'ip'],
            'except' => ['user'],
        ]);

        // no rateLimitIds is not a valid event; handler should
        // not be active
        $this->assertFalse($handler->isActive([]));

        $this->assertFalse($handler->isActive(['user']));
        $this->assertTrue($handler->isActive(['user', 'ip']));
        $this->assertTrue($handler->isActive(['ip']));
    }
}

class TestableBaseHandler extends BaseHandler
{
    public function isActive($rateLimitIds)
    {
        return parent::isActive($rateLimitIds);
    }
}
