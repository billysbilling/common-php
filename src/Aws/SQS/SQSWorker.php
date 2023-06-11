<?php

namespace Common\Aws\SQS;

use Carbon\Carbon;
use Common\Aws\Exception\SQSJobFailedException;

class SQSWorker extends SQSBase
{
    private string $queueName;
    public string $queueUrl;
    public int $waitTimeSeconds = 20;
    public int $maxNumberOfMessages = 10;
    public int $visibilityTimeout = 360;
    private ?SQSJob $currentJob = null;
    private bool $checkForMessages = true;
    private Carbon $queueStartedAt;

    public function listen(string $queueName, callable $workerProcess, callable $errorHandlerCallback = null): void
    {
        $this->queueName = $queueName;
        $this->queueUrl = $this->getQueueUrl($this->queueName);
        $this->queueStartedAt = Carbon::now();

        $this->printQueueStarted();

        while ($this->checkForMessages) {

            $this->getMessages(function (array $messages) use ($workerProcess, $errorHandlerCallback) {

                $totalCount = count($messages);
                $processCount = 0;

                foreach ($messages as $value) {
                    $processCount++;
                    try {
                        $this->currentJob = new SQSJob($value);

                        $this->log("Processing ($processCount of $totalCount): " . $this->currentJob->getMessageId());

                        // Process the job
                        $exitCode = $workerProcess($this->currentJob);

                        if ($exitCode === 0 || is_null($exitCode)) {
                            $this->ackMessage($value);
                            $this->log('Processed: ' . $this->currentJob->getMessageId());
                        } else {
                            $this->nackMessage($value);
                            $this->log('Failed: ' . $this->currentJob->getMessageId());
                        }
                    } catch (\Throwable $e) {

                        $this->nackMessage($value);
                        $this->log('Error: ' . $this->currentJob->getMessageId() . ' - ' . $e->getMessage());

                        $errorHandlerCallback(SQSJobFailedException::create($e, $this->currentJob), $this->currentJob?->attempts());
                    }
                }
            });
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
            if (Carbon::now()->gte($this->queueStartedAt->copy()->addHour())) {
                $this->checkForMessages = false;
            } else {
                sleep(10);
            }
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
        $this->log('**** Worker started on queue: ' . $this->queueName);
    }

    private function printQueueEnded(): void
    {
        $this->log('**** Worker finished on queue: ' . $this->queueName . '. (Started ' . $this->queueStartedAt->toDateTimeString() . ')');
    }

    private function log($message): void
    {
        echo $message . PHP_EOL;
    }
}
