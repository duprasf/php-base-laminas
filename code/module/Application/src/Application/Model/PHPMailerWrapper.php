<?php

namespace Application\Model;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as SMTP_Exception;
use Application\Interface\EmailerInterface;

class PHPMailerWrapper implements EmailerInterface
{
    public function __construct(bool $userException=true)
    {
        $this->setUseException($userException);
    }

    public function __invoke(...$data): bool
    {
        return $this->sendEmail(...$data);
    }

    public function reportException(Exception $e, ?String $extraMessage = null, ?String $appName = null, ?String $email = null): bool
    {
        return false;
    }

    public function reportError(array $error, ?String $recipient = null, ?String $template = null, ?String $apiKey = null, ?array $personalisation = []): bool
    {
        return false;
    }

    public function sendEmail(string $recipient, string $templateId, ?array $personalisation = [], ?string $apiKey = null): bool
    {
        $template = $this->templates[$templateId] ?? $templateId;

        $mail = $this->getPhpMailer();
        //Recipients
        $mail->setFrom($personalisation['from'] ?? 'no-reply@noemail.com');
        $mail->addAddress($recipient);

        //Content
        $body = $template['body'];
        $body = str_replace(array_map(function($v){return '(('.$v.'))';}, array_keys($personalisation)), $personalisation, $body);
        $mail->isHTML(true);
        $mail->Subject = $template['subject'];
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        if(!$mail->send()) {
            throw new SMTP_Exception('Could not send email');
        }
        return true;
    }

    private $useException;
    public function setUseException(bool $bool): self
    {
        $this->useException=$bool;
        return $this;
    }

    private $overwriteEmail;
    public function setOverwriteEmail(string $email): self
    {
        $this->overwriteEmail=$email;
        return $this;
    }

    private $appName;
    public function setAppName(string $name): self
    {
        $this->appName=$name;
        return $this;
    }

    protected $templates;
    public function setTemplates(array $templates, bool $replace=true): self
    {
        if($replace) {
            $this->templates = $templates;
            return $this;
        }
        $this->templates = array_merge($this->templates, $templates);
        return $this;
    }
    public function setTemplate(string $content, string $id, string $subject=''): self
    {
        $this->templates[$id] = ['body'=>$content, 'subject'=>$subject];
        return $this;
    }

    protected $phpMailer;
    public function setPhpMailer(PHPMailer $obj): self
    {
        $this->phpMailer = $obj;
        return $this;
    }
    protected function getPhpMailer(): PHPMailer
    {
        return $this->phpMailer;
    }

    public function __toString(): string
    {
        // TODO: add debug/error code as json string
        return '';
    }
}
