<?php

namespace Common\Aws\SQS;

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use Common\Aws\ClientFactory;

class SqsWorker
{
    public string $queueUrl;
    public int $sleep = 10;
    public int $waitTimeSeconds = 20;
    public int $maxNumberOfMessages = 1;
    public int $visibilityTimeout = 360;
    private SqsClient $sqsClient;

    public function __construct()
    {
        $this->sqsClient = ClientFactory::getSQSClient();
    }

    public function listen(string $queueUrl, callable $workerProcess, callable $errorHandlerCallback = null): void
    {

        $this->queueUrl = $queueUrl;

        $this->printQueueStarted();

        $checkForMessages = true;
        $counterCheck = 0;
        $errorCounter = 0;
        while ($checkForMessages) {
            $this->out('Check(' . $counterCheck . ') time: ' . date('Y-m-d H:i:s'));

            try {
                $this->out('Retrieving messages...');
                $this->getMessages(function (array $messages) use ($workerProcess) {
                    foreach ($messages as $value) {
                        $completed = $workerProcess($value);

                        if ($completed) {
                            $this->ackMessage($value);
                        } else {
                            $this->nackMessage($value);
                        }
                    }

                });

                $errorCounter = 0;

            } catch (AwsException $e) {

                if ($errorCounter >= 5) {
                    $checkForMessages = false;
                }
                $errorCounter++;

                // output error message if fails
                error_log($e->getMessage());

                if ($errorHandlerCallback !== null) {
                    $errorHandlerCallback($e->getMessage(), $errorCounter);
                }
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
            $counterCheck++;

        }

        $this->printQueueEnded();

    }

    private function getMessages(callable $callback): void
    {
        $result = $this->sqsClient->receiveMessage([
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => $this->maxNumberOfMessages,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => $this->queueUrl, // REQUIRED
            'WaitTimeSeconds' => $this->waitTimeSeconds,
            'VisibilityTimeout' => $this->visibilityTimeout,
        ]);

        $messages = $result->get('Messages');
        if ($messages !== null) {
            $this->out(count($messages) . " messages found");
            $callback($messages);
        } else {
            $this->out('No messages found');
            $sleep = $this->sleep;
            $this->out("Sleeping for $sleep seconds");
            sleep($sleep);
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
        echo PHP_EOL.PHP_EOL;
        echo PHP_EOL.'*****************************************************************';
        echo PHP_EOL.'**** Worker started at ' . date('Y-m-d H:i:s');
        echo PHP_EOL.'*****************************************************************';
    }

    private function printQueueEnded(): void
    {
        echo PHP_EOL.PHP_EOL;
        echo PHP_EOL.'*****************************************************************';
        echo PHP_EOL.'**** Worker finished at ' . date('Y-m-d H:i:s');
        echo PHP_EOL.'*****************************************************************';
        echo PHP_EOL.PHP_EOL;
    }

    private function out($message): void
    {
        echo PHP_EOL . $message;
    }

}
