<?php

namespace Common\Aws\Exception;

use Common\AWS\SQS\SQSJob;
use Exception;

class SQSJobFailedException extends Exception
{
    private ?SQSJob $SQSJob = null;

    public static function create(\Throwable $throwable, ?SQSJob $SQSJob = null): static
    {
        $exception = new static($throwable->getMessage(), $throwable->getCode(), $throwable);
        $exception->SQSJob = $SQSJob;
        return $exception;
    }

    public function latestJobData(): array | null
    {
        return $this->SQSJob?->toArray();
    }
}
