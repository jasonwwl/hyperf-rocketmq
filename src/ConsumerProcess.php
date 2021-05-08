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
use ReflectionClass;
use Turing\HyperfRocketmq\Annotation\Consumer as ConsumerAnnotation;
use Turing\HyperfRocketmq\MQ\Exception\MessageNotExistException;
use Turing\HyperfRocketmq\MQ\Model\Message;
use Turing\HyperfRocketmq\MQ\MQClient;
use Turing\HyperfRocketmq\MQ\MQConsumer;

class ConsumerProcess
{
    private MQClient $client;

    private MQConsumer $consumer;

    private ConsumerAnnotation $options;

    /**
     * @Inject
     */
    private ContainerInterface $container;

    private LoggerInterface $logger;

    private ConsumerInterface $instance;

    private array $tagAnnotations;

    private array $tagMethodMap = [];

    private array $methodParamMap = [];

    public function __construct(array $config, string $instanceClass, ConsumerAnnotation $options, array $tagAnnotations)
    {
        $this->client = new MQClient(
            $config['endpoint'],
            $config['access_id'],
            $config['access_key']
        );
        $this->options = $options;
        $this->consumer = $this->client->getConsumer($config['instance_id'], $options->topic, $config['group']);
        $this->logger = $this->container->get(StdoutLoggerInterface::class);
        $this->instance = make($instanceClass, [$this->consumer]);
        // $relClass = new ReflectionClass(get_class($this->instance));
        $relClass = new ReflectionClass($instanceClass);
        foreach ($tagAnnotations as $tag => $meta) {
            $method = $meta['method'];
            $this->tagMethodMap[$tag] = $method;
            $this->tagAnnotations[$tag] = $meta['annotation'];
            $relMethod = $relClass->getMethod($method);
            $params = $relMethod->getParameters();
            if (isset($params[0]) && $params[0]->getType() && ! $params[0]->getType()->isBuiltin()) {
                $this->methodParamMap[$method] = $params[0]->getType()->getName();
            }
        }
    }

    public function consume()
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
                    $method = $this->tagMethodMap[$message->getMessageTag()];
                    if ($method) {
                        $decode = json_decode($message->getMessageBody(), true);
                        if (isset($this->methodParamMap[$method])) {
                            $msg = new $this->methodParamMap[$method]();
                            foreach ($decode as $k => $v) {
                                $msg->{$k} = $v;
                            }
                        } else {
                            $msg = $decode;
                        }
                        $decode = json_decode($message->getMessageBody());
                        $result = $this->instance->{$method}($msg, $message->getMessageTag(), $message->getReceiptHandle());
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
