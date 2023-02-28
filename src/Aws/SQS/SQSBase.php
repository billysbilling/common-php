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
}
