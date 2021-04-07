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
namespace Turing\HyperfRocketmq\MQ;

use Turing\HyperfRocketmq\MQ\Exception\InvalidArgumentException;
use Turing\HyperfRocketmq\MQ\Http\HttpClient;
use Turing\HyperfRocketmq\MQ\Model\TopicMessage;
use Turing\HyperfRocketmq\MQ\Requests\PublishMessageRequest;
use Turing\HyperfRocketmq\MQ\Responses\PublishMessageResponse;

class MQProducer
{
    protected $instanceId;

    protected $topicName;

    protected $client;

    public function __construct(HttpClient $client, $instanceId = null, $topicName)
    {
        if (empty($topicName)) {
            throw new InvalidArgumentException(400, 'TopicName is null');
        }
        $this->instanceId = $instanceId;
        $this->client = $client;
        $this->topicName = $topicName;
    }

    public function getInstanceId()
    {
        return $this->instanceId;
    }

    public function getTopicName()
    {
        return $this->topicName;
    }

    public function publishMessage(TopicMessage $topicMessage)
    {
        $request = new PublishMessageRequest(
            $this->instanceId,
            $this->topicName,
            $topicMessage->getMessageBody(),
            $topicMessage->getProperties(),
            $topicMessage->getMessageTag()
        );
        $response = new PublishMessageResponse();
        return $this->client->sendRequest($request, $response);
    }
}
