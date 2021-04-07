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
namespace Turing\HyperfRocketmq\MQ\Model;

use Turing\HyperfRocketmq\MQ\Constants;

class AckMessageErrorItem
{
    protected $errorCode;

    protected $errorMessage;

    protected $receiptHandle;

    public function __construct($errorCode, $errorMessage, $receiptHandle)
    {
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->receiptHandle = $receiptHandle;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function getReceiptHandle()
    {
        return $this->receiptHandle;
    }

    public static function fromXML($xmlReader)
    {
        $errorCode = null;
        $errorMessage = null;
        $receiptHandle = null;

        while ($xmlReader->read()) {
            switch ($xmlReader->nodeType) {
            case \XMLReader::ELEMENT:
                switch ($xmlReader->name) {
                case Constants::ERROR_CODE:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT) {
                        $errorCode = $xmlReader->value;
                    }
                    break;
                case Constants::ERROR_MESSAGE:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT) {
                        $errorMessage = $xmlReader->value;
                    }
                    break;
                case Constants::RECEIPT_HANDLE:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT) {
                        $receiptHandle = $xmlReader->value;
                    }
                    break;
                }
                break;
            case \XMLReader::END_ELEMENT:
                if ($xmlReader->name == Constants::ERROR) {
                    return new AckMessageErrorItem($errorCode, $errorMessage, $receiptHandle);
                }
                break;
            }
        }

        return new AckMessageErrorItem($errorCode, $errorMessage, $receiptHandle);
    }
}