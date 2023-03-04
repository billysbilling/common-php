<?php

namespace Common\Aws\SQS;

class SQSWorker extends SQSBase
{
    public string $queueUrl;
    public int $sleep = 10;
    public int $waitTimeSeconds = 20;
    public int $maxNumberOfMessages = 1;
    public int $visibilityTimeout = 360;

    public function listen(string $queueName, callable $workerProcess, callable $errorHandlerCallback = null): void
    {
        $this->queueUrl = $this->getQueueUrl($queueName);

        $this->printQueueStarted();

        $checkForMessages = true;
        $errorCounter = 0;
        while ($checkForMessages) {
            try {
                $this->getMessages(function (array $messages) use ($workerProcess) {
                    foreach ($messages as $value) {
                        $job = new SQSJob($value);
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

                $errorCounter = 0;

            } catch (\Throwable $e) {

                if ($errorCounter >= 5) {
                    $checkForMessages = false;

                    if ($errorHandlerCallback !== null) {
                        $errorHandlerCallback($e, $errorCounter);
                    }
                }
                $errorCounter++;
                error_log($e->getMessage());
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
            sleep($this->sleep);
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
            'VisibilityTimeout' => 10,
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
        echo PHP_EOL . $message;
    }
}
