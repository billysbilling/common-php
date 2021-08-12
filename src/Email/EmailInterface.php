<?php

namespace Common\Email;

interface EmailInterface
{
    public function sendEmailWithTemplate(string $from, string $to, string $templateAlias, array $templateModel): void;
}
