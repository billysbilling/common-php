<?php

namespace Common\Aws\SQS;

use Aws\Result;

class SQSMessenger extends SQSBase
{
    public function publish(
        string $queueName,
        string $message,
        array $messageAttributes = [],
        int $delaySeconds = 0,
        string $messageGroupId = '',
        string $messageDeduplicationId = ''
    ): Result {
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

        return $this->sqsClient->sendMessage($params);
    }
}
