<?php

namespace Application\Interface;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as SMTP_Exception;
use Application\Interface\EmailerInterface;

class PHPMailer implements EmailerInterface
{
    public function __construct(bool $userException=true)
    {
        $this->setUseException($userException);
    }

    public function __invoke(...$data): bool
    {
        return $this->sendEmail(...$data);
    }

    public function sendAuthenticationEmail(string $recipient, string $template, string $url, null|string $apiKey = null): bool
    {
        return false;
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
        $mail = new PHPMailer(true);

        $template = $this->templates[$templateId] ?? $templateId;
        try {
            //Server settings
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER; //Enable verbose debug output
            $mail->isSMTP();
            $mail->Host       = getenv('SMTP_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USERNAME');
            $mail->Password   = getenv('SMTP_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = getenv('SMTP_PORT');

            //Recipients
            $mail->setFrom($personalisation['from'] ?? $this->getUser()->email);
            $mail->addAddress($recipient);

            //Content
            $body = $template['body'];
            $body = str_replace(array_keys($personalisation), $personalisation, $body);
            $mail->isHTML(true);
            $mail->Subject = $template['subject'];
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            if(!$mail->send()) {
                throw new SMTP_Exception('Could not send email');
            }
            return true;
        } catch (SMTP_Exception $e) {
            return false;
        }
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
    public function setTemplates(array $templates)
    {
        $this->templates = $templates;
        return $this;
    }
    public function setTemplate($name, $id)
    {
        $this->templates[$name] = $id;
        return $this;
    }

    public function __toString(): string
    {
        // TODO: add debug/error code as json string
        return '';
    }
}
