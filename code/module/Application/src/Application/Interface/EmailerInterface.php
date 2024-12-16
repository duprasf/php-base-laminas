<?php

namespace Application\Interface;

use Exception;

interface EmailerInterface
{
    public function setUseException(bool $bool): self;
    public function setOverwriteEmail(string $email): self;
    public function setAppName(string $name): self;
    public function __toString(): string;

    public function __invoke(...$data): bool;
    public function sendEmail(string $recipient, string $templateId, ?array $personalisation = [], ?string $apiKey = null): bool;

    public function reportException(Exception $e, ?String $extraMessage = null, ?String $appName = null, ?String $email = null): bool;
    public function reportError(array $error, ?String $recipient = null, ?String $template = null, ?String $apiKey = null, ?array $personalisation = []): bool;

    public function setTemplates(array $templates, bool $replace = true): self;
    public function setTemplate(string $name, string $template): self;
}
