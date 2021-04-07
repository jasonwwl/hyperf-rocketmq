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

use Exception;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Psr\Container\ContainerInterface;
use Turing\HyperfRocketmq\Annotation\Producer as AnnotationProducer;
use Turing\HyperfRocketmq\MQ\Model\TopicMessage;
use Turing\HyperfRocketmq\MQ\MQClient;

class Producer
{
    private MQClient $client;

    private ContainerInterface $container;

    private array $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $configUtil = $this->container->get(ConfigInterface::class);
        $config = $configUtil->get('rocketmq.default');
        $this->config = $config;
        $this->client = new MQClient(
            $config['endpoint'],
            $config['access_id'],
            $config['access_key']
        );
    }

    public function publish(Message $message)
    {
        /**
         * @var AnnotationProducer $annotation
         */
        $annotation = AnnotationCollector::getClassAnnotation(get_class($message), AnnotationProducer::class);
        if (! $annotation) {
            throw new Exception('RocketMQ消息必须通过"Annotation\Producer"注解指定tag和topic');
        }
        if (! $annotation->topic) {
            throw new Exception('RocketMQ消息必须通过"Annotation\Producer"注解指定topic');
        }
        if (! $annotation->tag) {
            throw new Exception('RocketMQ消息必须通过"Annotation\Producer"注解指定tag');
        }
        $publishMsg = new TopicMessage(json_encode($message));
        $publishMsg->setMessageTag($annotation->tag);

        if ($message->_deliver_time > 0) {
            $publishMsg->setStartDeliverTime($message->_deliver_time);
        }

        if ($message->_message_key) {
            $publishMsg->setMessageKey($message->_message_key);
        }

        $producer = $this->client->getProducer($this->config['instance_id'], $annotation->topic);
        $producer->publishMessage($publishMsg);
    }
}
