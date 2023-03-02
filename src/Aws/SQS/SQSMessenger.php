<?php

namespace Common\Aws\SQS;

use Aws\Exception\AwsException;
use Aws\Result;

class SQSMessenger extends SQSBase
{
    public int $retryTimesOnFail = 2;
    public int $waitBeforeRetry = 1;

    public function publish(
        string $queueName,
        string $message,
        array $messageAttributes = [],
        int $delaySeconds = 0,
        string $messageGroupId = '',
        string $messageDeduplicationId = ''
    ): Result|null {
        $params = [
            'QueueUrl' => $this->getQueueUrl($queueName),
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
                    $result = null;
                    $tryAgain = true;

                    if ($errorCounter >= $this->retryTimesOnFail) {
                        break;
                    }

                    if ($this->waitBeforeRetry > 0) {
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
