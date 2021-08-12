<?php

namespace Common\Email;

interface EmailClientInterface
{
    public function sendEmailWithTemplate(string $from, string $to, string $templateAlias, array $templateModel): void;
}
