<?php

namespace Common\Aws;

use Aws\Exception\AwsException;
use Aws\Result;
use Common\Aws\SQS\SQSBase;

class SQSMessenger extends SQSBase
{
    public int $retryTimesOnFail = 2;
    public int $waitBeforeRetry = 1;

    public function publish(
        string $queueUrl,
        array $message,
        array $messageAttributes = [],
        int $delaySeconds = 10,
        string $messageGroupId = '',
        string $messageDeduplicationId = ''
    ): Result|bool|null {
        $params = [
            'QueueUrl' => $queueUrl,
            'MessageBody' => $message,
            'MessageAttributes' => $messageAttributes,
        ];

        if ($delaySeconds) {
            $params['DelaySeconds'] = $delaySeconds;
        }

        if ($messageGroupId) {
            $params['MessageGroupId'] = $messageGroupId;
        }

        if ($messageDeduplicationId) {
            $params['MessageDeduplicationId'] = $messageDeduplicationId;
        }

        $tryAgain = false;
        $errorCounter = 0;
        $result = null;
        do {
            try {
                $result = $this->sqsClient->sendMessage($params);
                $tryAgain = false;
            } catch (AwsException $e) {

                if ($this->retryTimesOnFail > 0) {
                    $result = false;
                    $tryAgain = true;

                    if ($errorCounter >= $this->retryTimesOnFail) {
                        break;
                    }

                    if ($errorCounter >= 2 && $this->waitBeforeRetry > 0) {
                        sleep($this->waitBeforeRetry);
                    }

                    error_log($e->getMessage());
                    $errorCounter++;
                }

            }

        } while ($tryAgain);

        return $result;
    }
}
