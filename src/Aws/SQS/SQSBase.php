<?php

namespace Common\Aws\SQS;

use Aws\Sqs\SqsClient;
use Common\Aws\ClientFactory;

abstract class SQSBase
{
    protected SqsClient $sqsClient;
    public function __construct()
    {
        $this->sqsClient ??= ClientFactory::getSQSClient();
    }

    private function getQueueUrlPrefix(): string
    {
        $sqsPrefix = getenv('AWS_SQS_URL_PREFIX');
        if (!$sqsPrefix) {
            throw new \Exception('Environment variable AWS_SQS_URL_PREFIX not set.');
        }

        return $sqsPrefix;
    }

    public function getQueueUrl(string $queueName): string
    {
        return $this->getQueueUrlPrefix() . $queueName;
    }
}
