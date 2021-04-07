<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Turing\HyperfRocketmq\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Consumer extends AbstractAnnotation
{
    public string $topic = '';

    /**
     * topic类型：normal order.
     */
    public string $type = 'normal';

    /**
     * 每次消费几条消息.
     */
    public int $numOfMessages = 3;

    /**
     * 获取消息的请求长轮询时间.
     */
    public int $waitSeconds = 3;

    /**
     * 是否开启这个消费者.
     */
    public bool $enable = true;

    /**
     * 消费者进程数(worker).
     */
    public int $numOfProcess = 1;
}
