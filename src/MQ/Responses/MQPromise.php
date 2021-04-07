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
namespace Turing\HyperfRocketmq\MQ\Responses;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Turing\HyperfRocketmq\MQ\Exception\MQException;

class MQPromise
{
    private $response;

    private $promise;

    public function __construct(PromiseInterface &$promise, BaseResponse &$response)
    {
        $this->promise = $promise;
        $this->response = $response;
    }

    public function isCompleted()
    {
        return $this->promise->getState() != 'pending';
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getState()
    {
        return $this->promise->getState();
    }

    public function wait()
    {
        $message = 'Unknown';
        $code = 500;
        try {
            $res = $this->promise->wait();
            if ($res instanceof ResponseInterface) {
                $this->response->setRequestId($res->getHeaderLine('x-mq-request-id'));
                return $this->response->parseResponse($res->getStatusCode(), $res->getBody()->getContents());
            }
        } catch (MQException $e) {
            throw $e;
        } catch (RequestException $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            if ($e->hasResponse()) {
                $message = $e->getResponse()->getBody()->getContents();
                $this->response->parseErrorResponse($e->getCode(), $message);
            }
        } catch (TransferException $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
        } catch (Throwable $t) {
            $message = $t->getMessage();
            $code = $t->getCode();
        }
        throw new MQException($code, $message);
    }
}
