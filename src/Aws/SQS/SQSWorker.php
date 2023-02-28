<?php

namespace Common\Aws\SQS;

use Aws\Exception\AwsException;

class SQSWorker extends SQSBase
{
    public string $queueUrl;
    public int $sleep = 10;
    public int $waitTimeSeconds = 20;
    public int $maxNumberOfMessages = 1;
    public int $visibilityTimeout = 360;

    public function listen(string $queueUrl, callable $workerProcess, callable $errorHandlerCallback = null): void
    {

        $this->queueUrl = $queueUrl;

        $this->printQueueStarted();

        $checkForMessages = true;
        $errorCounter = 0;
        while ($checkForMessages) {
            try {
                $this->getMessages(function (array $messages) use ($workerProcess) {
                    foreach ($messages as $value) {
                        $job = new SQSJob($value);
                        $this->out('Processing ' . $job->getMessageId());
                        $completed = $workerProcess($job);

                        if ($completed) {
                            $this->ackMessage($value);
                            $this->out('Processed ' . $job->getMessageId());
                        } else {
                            $this->nackMessage($value);
                            $this->out('Failed ' . $job->getMessageId());
                        }
                    }

                });

                $errorCounter = 0;

            } catch (AwsException $e) {

                if ($errorCounter >= 5) {
                    $checkForMessages = false;
                }
                $errorCounter++;
                error_log($e->getMessage());

                if ($errorHandlerCallback !== null) {
                    $errorHandlerCallback($e->getMessage(), $errorCounter);
                }
            } catch (\Exception $e) {
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
            $this->out(count($messages) . " messages found");
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
            'VisibilityTimeout' => 0,
            'QueueUrl' => $this->queueUrl,
            'ReceiptHandle' => $message['ReceiptHandle'],
        ]);
    }

    private function printQueueStarted(): void
    {
        $this->out(PHP_EOL);
        $this->out('*****************************************************************');
        $this->out('**** Worker started at ' . date('Y-m-d H:i:s'));
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

    private function out($message): void
    {
        echo PHP_EOL . $message;
    }

}
