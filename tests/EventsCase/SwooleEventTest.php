<?php

declare(strict_types=1);

namespace Workbunny\Tests\EventsCase;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Workbunny\WebmanCoroutine\Events\SwooleEvent;
use Workbunny\WebmanCoroutine\Exceptions\EventLoopException;
use Workerman\Events\EventInterface;

/**
 * @runTestsInSeparateProcesses
 */
class SwooleEventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 定义缺失的常量
        if (!defined('SWOOLE_EVENT_READ')) {
            define('SWOOLE_EVENT_READ', 1);
        }
        if (!defined('SWOOLE_EVENT_WRITE')) {
            define('SWOOLE_EVENT_WRITE', 2);
        }
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testConstructWithoutSwooleExtension()
    {
        if (extension_loaded('swoole')) {
            $this->markTestSkipped('Swoole extension is loaded.');
        }
        // normal
        $this->expectException(EventLoopException::class);
        $this->expectExceptionMessage('Not support ext-swoole.');
        new SwooleEvent();

        // debug
        $swooleEvent = new SwooleEvent(true);
        $this->assertInstanceOf(SwooleEvent::class, $swooleEvent);
    }

    public function testAddSignal()
    {
        $swooleEvent = new SwooleEvent(true);

        $processMock = m::mock('alias:Swoole\Process');
        $processMock->shouldReceive('signal')->andReturn(true);

        $result = $swooleEvent->add(SIGTERM, EventInterface::EV_SIGNAL, function () {
            echo 'Signal received';
        });

        $this->assertTrue($result);
    }

    public function testAddTimer()
    {
        $swooleEvent = new SwooleEvent(true);

        $timerMock = m::mock('alias:Swoole\Timer');
        $timerMock->shouldReceive('after')->andReturn(1);

        $result = $swooleEvent->add(1, EventInterface::EV_TIMER, function () {
            echo 'Timer triggered';
        });

        $this->assertEquals(0, $result);
    }

    public function testAddRead()
    {
        $swooleEvent = new SwooleEvent(true);

        $eventMock = m::mock('alias:Swoole\Event');
        $eventMock->shouldReceive('add')->andReturn(true);

        $stream = fopen('php://memory', 'r+');
        $result = $swooleEvent->add($stream, EventInterface::EV_READ, function () {
            echo 'Read event';
        });

        $this->assertTrue($result);
        fclose($stream);
    }

    public function testDelSignal()
    {
        $swooleEvent = new SwooleEvent(true);

        $processMock = m::mock('alias:Swoole\Process');
        $processMock->shouldReceive('signal')->andReturn(true);

        $swooleEvent->add(SIGTERM, EventInterface::EV_SIGNAL, function () {
            echo 'Signal received';
        });

        $result = $swooleEvent->del(SIGTERM, EventInterface::EV_SIGNAL);

        $this->assertTrue($result);
    }

    public function testDelTimer()
    {
        $swooleEvent = new SwooleEvent(true);

        $timerMock = m::mock('alias:Swoole\Timer');
        $timerMock->shouldReceive('after')->andReturn(1);
        $timerMock->shouldReceive('clear')->andReturn(true);

        $timerId = $swooleEvent->add(1, EventInterface::EV_TIMER, function () {
            echo 'Timer triggered';
        });

        $result = $swooleEvent->del($timerId, EventInterface::EV_TIMER);

        $this->assertTrue($result);
    }

    public function testLoop()
    {
        $this->markTestSkipped('loop will exit()');

        $this->expectException(\RuntimeException::class);

        $swooleEvent = new SwooleEvent(true);
        $eventMock = m::mock('alias:Swoole\Event');
        $eventMock->shouldReceive('wait')->andReturn(true);

        $this->expectOutputString('');
        $swooleEvent->loop();
    }

    public function testDestroy()
    {
        $swooleEvent = new SwooleEvent(true);

        $eventMock = m::mock('alias:Swoole\Event');
        $eventMock->shouldReceive('exit')->andReturn(true);

        $eventMock = m::mock('alias:Swoole\Coroutine');
        $eventMock->shouldReceive('listCoroutines')->andReturn([]);

        $swooleEvent->destroy();

        $this->assertEmpty($swooleEvent->getTimerCount());
    }

    public function testClearAllTimer()
    {
        $swooleEvent = new SwooleEvent(true);

        $timerMock = m::mock('alias:Swoole\Timer');
        $timerMock->shouldReceive('clear')->andReturn(true);
        $timerMock->shouldReceive('after')->andReturnSelf();

        $swooleEvent->add(1, EventInterface::EV_TIMER, function () {
            echo 'Timer triggered';
        });

        $swooleEvent->clearAllTimer();

        $this->assertEquals(0, $swooleEvent->getTimerCount());
    }
}
