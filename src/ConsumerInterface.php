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
namespace Turing\HyperfRocketmq;

use Turing\HyperfRocketmq\MQ\MQConsumer;

// use Turing\HyperfRocketmq\MQ\Model\Message;

interface ConsumerInterface
{
    public function __construct(MQConsumer $mQConsumer);

    public function ack(string $handle);

    // public function consume(Message $message, string $tag);
}
