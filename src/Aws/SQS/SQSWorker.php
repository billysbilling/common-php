<?php

namespace Common\Aws\SQS;

use Exception;

class SQSWorker extends SQSBase
{
    public string $queueUrl;
    public int $sleep = 10;
    public int $waitTimeSeconds = 20;
    public int $maxNumberOfMessages = 1;
    public int $visibilityTimeout = 360;

    private const LEVEL_SUCCESS = 'success';
    private const LEVEL_WARNING = 'warning';
    private const LEVEL_DANGER = 'danger';

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
                        $this->out('Processing ' . $job->getMessageId(), self::LEVEL_WARNING);
                        $this->out($job->toJson());
                        $completed = $workerProcess($job);

                        if ($completed) {
                            $this->ackMessage($value);
                            $this->out('Processed ' . $job->getMessageId(), self::LEVEL_SUCCESS);
                        } else {
                            $this->nackMessage($value);
                            $this->out('Failed ' . $job->getMessageId() . '. ', self::LEVEL_DANGER);
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
            'AttributeNames' => ['SentTimestamp'],
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
        $this->out(PHP_EOL);
        $this->out('*****************************************************************');
        $this->out('**** Worker started at ' . date('Y-m-d H:i:s'));
        $this->out('**** ' . $this->queueUrl);
        $this->out('*****************************************************************');
    }

    private function printQueueEnded(): void
    {
        $this->out(PHP_EOL);
        $this->out('*****************************************************************');
        $this->out('**** Worker finished at ' . date('Y-m-d H:i:s'));
        $this->out('*****************************************************************');
        $this->out(PHP_EOL);
    }

    private function out($message, ?string $level = null): void
    {
        echo PHP_EOL . $this->getCLIColor($level) . $message . "\e[0m";
    }

    private function getCLIColor(string $level = null): string
    {
        return match ($level) {
            self::LEVEL_SUCCESS => "\e[0;32m",
            self::LEVEL_WARNING => "\e[0;33m",
            self::LEVEL_DANGER => "\e[0;31m",
            default => "\e[0m",
        };
    }

}
