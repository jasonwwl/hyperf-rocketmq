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

class BaseConsumer implements ConsumerInterface
{
    public MQConsumer $consumer;

    public function __construct(MQConsumer $consumer)
    {
        $this->consumer = $consumer;
    }

    public function ack(string $handle)
    {
        return $this->consumer->ackMessage([$handle]);
    }
}
