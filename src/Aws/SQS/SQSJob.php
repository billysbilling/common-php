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

    public function attempts(): int
    {
        return (int) $this->data['Attributes']['ApproximateReceiveCount'];
    }

    public function getAttributes()
    {
        return $this->data['Attributes'];
    }

    public function toArray(): array
    {
        $body = $this->getBody();
        return [
            'Body' => $this->isBase64($body) ? base64_decode($body) : $body,
            'MessageId' => $this->data['MessageId'],
            'Attributes' => $this->data['Attributes'],
        ];
    }

    public function toJson(): string|false
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    private function isBase64(string $value): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $value);
    }
}
