<?php

namespace Common\Email;

use Postmark\PostmarkClient;

class Postmark
{
    private PostmarkClient $client;

    public function getClient(string $token): Postmark
    {
        $this->client = new PostmarkClient($token);

        return $this;
    }

    public function sendEmailWithTemplate(string $from, string $to, string $templateAlias, array $templateModel)
    {
        if (is_null($this->client)) {
            throw new \Exception('PostmarkClient not initialized. Please call getClient(string $token) before calling sendEmailWithTemplate()');
        }

        $this->client->sendEmailWithTemplate(from: $from, to: $to, templateIdOrAlias:  $templateAlias, templateModel: $templateModel);
    }
}
