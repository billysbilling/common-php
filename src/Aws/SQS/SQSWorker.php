<?php

namespace Common\Aws\SQS;

use Common\Aws\Exception\SQSJobFailedException;

class SQSWorker extends SQSBase
{
    public string $queueUrl;
    public int $waitTimeSeconds = 20;
    public int $maxNumberOfMessages = 1;
    public int $visibilityTimeout = 360;
    private ?SQSJob $latestSQSJob = null;
    private bool $checkForMessages = true;

    public function listen(string $queueName, callable $workerProcess, callable $errorHandlerCallback = null): void
    {
        $this->queueUrl = $this->getQueueUrl($queueName);

        $this->printQueueStarted();

        while ($this->checkForMessages) {
            try {
                $this->getMessages(function (array $messages) use ($workerProcess) {
                    foreach ($messages as $value) {
                        $job = new SQSJob($value);
                        $this->latestSQSJob = $job;
                        $this->log('Processing: ' . $job->getMessageId());
                        $exitCode = $workerProcess($job);

                        if ($exitCode === 0 || is_null($exitCode)) {
                            $this->ackMessage($value);
                            $this->log('Processed: ' . $job->getMessageId());
                        } else {
                            $this->nackMessage($value);
                            $this->log('Failed: ' . $job->getMessageId());
                        }
                    }

                });
            } catch (\Throwable $e) {
                $errorHandlerCallback(SQSJobFailedException::create($e, $this->latestSQSJob), $this->latestSQSJob?->attempts());
            }
        }

        $this->printQueueEnded();

    }

    private function getMessages(callable $callback): void
    {
        $result = $this->sqsClient->receiveMessage([
            'AttributeNames' => ['SentTimestamp', 'ApproximateReceiveCount'],
            'MaxNumberOfMessages' => $this->maxNumberOfMessages,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => $this->queueUrl,
            'WaitTimeSeconds' => $this->waitTimeSeconds,
            'VisibilityTimeout' => $this->visibilityTimeout,
        ]);

        $messages = $result->get('Messages');
        if ($messages !== null) {
            $callback($messages);
        } else {
            $this->checkForMessages = false;
        }
    }

    private function ackMessage(array $message): void
    {
        $this->sqsClient->deleteMessage([
            'QueueUrl' => $this->queueUrl,
            'ReceiptHandle' => $message['ReceiptHandle'],
        ]);
    }

    private function nackMessage(array $message): void
    {
        $this->sqsClient->changeMessageVisibility([
            'VisibilityTimeout' => 0,
            'QueueUrl' => $this->queueUrl,
            'ReceiptHandle' => $message['ReceiptHandle'],
        ]);
    }

    private function printQueueStarted(): void
    {
        $this->log('**** Worker started on queue: ' . $this->queueUrl);
    }

    private function printQueueEnded(): void
    {
        $this->log('**** Worker finished on queue: ' . $this->queueUrl);
    }

    private function log($message): void
    {
        echo $message . PHP_EOL;
    }
}
