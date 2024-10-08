<?php

namespace Application\Model;

use Exception;

interface EmailerInterface
{
    public function setUseException(bool $bool): self;
    public function setOverwriteEmail($email): self;
    public function setAppName($name): self;
    public function __toString(): string;
    public function __invoke(...$data): bool;
    public function sendAuthenticationEmail(string $recipient, string $template, string $url, null|string $apiKey = null): bool;
    public function reportException(Exception $e, ?String $extraMessage = null, ?String $appName = null, ?String $email = null): bool;
    public function reportError(array $error, ?String $recipient = null, ?String $template = null, ?String $apiKey = null, ?array $personalisation = []): bool;
    public function sendEmail(string $recipient, string $templateId, ?array $personalisation = [], ?string $apiKey = null): bool;
}
