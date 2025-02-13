<?php
/**
 * @author workbunny/Chaz6chez
 * @email chaz6chez1993@outlook.com
 */
declare(strict_types=1);

namespace Workbunny\WebmanCoroutine\Handlers;

use Workerman\Worker;
use function Workbunny\WebmanCoroutine\package_installed;

/**
 * 基于Ripple插件的协程处理器，支持PHP-fiber
 */
class RippleWorkerman5Handler implements HandlerInterface
{

    /** @inheritdoc  */
    public static function isAvailable(): bool
    {
        return version_compare(static::_getWorkerVersion(), '5.0.0', '>=') and package_installed('cclilshy/p-ripple-drive');
    }

    /**
     * 为了测试可以mock
     *
     * @return string
     */
    protected static function _getWorkerVersion(): string
    {
        return Worker::VERSION;
    }

    /**
     * ripple handler无需初始化
     *
     * @inheritdoc
     */
    public static function initEnv(): void
    {
    }

    /** @inheritdoc  */
    public static function waitFor(?\Closure $closure = null, float|int $timeout = -1): void
    {
        $time = microtime(true);
        while (true) {
            if ($closure and call_user_func($closure) === true) {
                return;
            }
            if ($timeout > 0 && microtime(true) - $time >= $timeout) {
                return;
            }
            static::_sleep($timeout);
        }
    }

    /**
     * @param int|float $second
     * @return void
     */
    protected static function _sleep(int|float $second): void
    {
        \Co\sleep(max($second, 0));
    }
}
