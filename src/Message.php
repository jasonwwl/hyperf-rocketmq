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

class Message
{
    public float $_deliver_time = 0;

    public string $_message_key = '';

    public string $_topic = '';

    public string $_tag = '';

    public string $_receipt_handle = '';

    public function __construct(string $tag = '', string $topic = '', float $deliverTime = 0)
    {
        $this->setTopic($topic);

        $this->setTag($tag);

        $this->setStartDeliverTime($deliverTime);
    }

    /**
     * 定时消息，单位毫秒（ms），在指定时间戳（当前时间之后）进行投递。
     * 如果被设置成当前时间戳之前的某个时刻，消息将立刻投递给消费者.
     */
    public function setStartDeliverTime(float $ms)
    {
        $this->_deliver_time = $ms;
    }

    /**
     * 设置消息KEY，如果没有设置，则消息的KEY为RequestId.
     */
    public function setMessageKey(string $key)
    {
        $this->_message_key = $key;
    }

    public function setTopic(string $topic)
    {
        $this->_topic = $topic;
    }

    public function setTag(string $tag)
    {
        $this->_tag = $tag;
    }

    public function getTopic()
    {
        return $this->_topic;
    }

    public function getTag()
    {
        return $this->_tag;
    }

    public function getReceiptHandle()
    {
        return $this->_receipt_handle;
    }

    public function setReceiptHandle(string $hd)
    {
        return $this->_receipt_handle = $hd;
    }
}
