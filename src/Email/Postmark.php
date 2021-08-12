<?php

namespace Common\Email;

use Postmark\PostmarkClient;

class Postmark implements EmailInterface
{
    private PostmarkClient $client;

    public function __construct(string $token)
    {
        $this->client = new PostmarkClient($token);
    }

    public function sendEmailWithTemplate(string $from, string $to, string $templateAlias, array $templateModel): void
    {
        $this->client->sendEmailWithTemplate(from: $from, to: $to, templateIdOrAlias:  $templateAlias, templateModel: $templateModel);
    }
}
