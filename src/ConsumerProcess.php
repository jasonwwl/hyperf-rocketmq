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
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Turing\HyperfRocketmq\Annotation\Consumer as ConsumerAnnotation;
use Turing\HyperfRocketmq\Annotation\ConsumerTag;
use Turing\HyperfRocketmq\Annotation\Producer as ProducerAnnotation;
use Turing\HyperfRocketmq\Message as ProducerMessage;
use Turing\HyperfRocketmq\MQ\Exception\MessageNotExistException;
use Turing\HyperfRocketmq\MQ\Model\Message;
use Turing\HyperfRocketmq\MQ\MQClient;
use Turing\HyperfRocketmq\MQ\MQConsumer;

class ConsumerProcess
{
    private MQClient $client;

    private MQConsumer $consumer;

    private ConsumerAnnotation $options;

    private array $messageAnnotations;

    /**
     * @Inject
     */
    private ContainerInterface $container;

    private LoggerInterface $logger;

    public function __construct(array $config, ConsumerAnnotation $options, array $messageAnnotations)
    {
        $this->client = new MQClient(
            $config['endpoint'],
            $config['access_id'],
            $config['access_key']
        );
        $this->options = $options;
        $this->consumer = $this->client->getConsumer($config['instance_id'], $options->topic, $config['group']);
        $this->messageAnnotations = $messageAnnotations;
        $this->logger = $this->container->get(StdoutLoggerInterface::class);
    }

    public function consume(ConsumerInterface $consumer, array $tagsAnnotation)
    {
        while (true) {
            try {
                $messages = $this->consumer->consumeMessage($this->options->numOfMessages, $this->options->waitSeconds);
            } catch (MessageNotExistException $e) {
                continue;
            } catch (Exception $e) {
                $this->logger->error($e);
                sleep(3);
                continue;
            }
            /**
             * @var Message $message
             */
            foreach ($messages as $message) {
                try {
                    $method = null;
                    /**
                     * @var ConsumerTag $tagAnnotation
                     * @var string $methodName
                     */
                    foreach ($tagsAnnotation as $methodName => $tagAnnotation) {
                        if ($tagAnnotation->tag === $message->getMessageTag()) {
                            $method = $methodName;
                        }
                    }
                    if ($method) {
                        /**
                         * @var ProducerAnnotation $annotation
                         * @var string $messageClass
                         */
                        foreach ($this->messageAnnotations as $messageClass => $annotation) {
                            if ($this->options->topic === $annotation->topic && $message->getMessageTag() === $annotation->tag) {
                                /**
                                 * @var ProducerMessage $messageInstance
                                 */
                                $messageInstance = new $messageClass();
                                $decode = json_decode($message->getMessageBody(), true);
                                foreach ($decode as $k => $v) {
                                    $messageInstance->{$k} = $v;
                                }
                            }
                        }
                        $result = $consumer->{$method}($messageInstance);
                    } else {
                        $result = true;
                    }
                } catch (Exception $e) {
                    $this->logger->error($e);
                }
                if ($result === true) {
                    try {
                        $this->consumer->ackMessage([$message->getReceiptHandle()]);
                    } catch (Exception $e) {
                        $this->logger->error($e);
                    }
                }
            }
        }
    }
}
