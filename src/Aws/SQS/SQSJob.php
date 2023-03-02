<?php

namespace Common\AWS\SQS;

class SQSJob
{
    public function __construct(private array $data)
    {
    }

    public function getBody()
    {
        return $this->data['Body'];
    }

    public function getMessageId()
    {
        return $this->data['MessageId'];
    }

    public function getAttributes()
    {
        return $this->data['Attributes'];
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson(): string|false
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }
}
