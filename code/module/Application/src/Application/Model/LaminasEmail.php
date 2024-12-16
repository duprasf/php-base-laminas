<?php
namespace Application\Model;

use Exception;
use RuntimeException;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Application\Interface\EmailerInterface;
use UserAuth\Model\User\UserAwareInterface;
use UserAuth\Trait\UserAwareTrait;

class LaminasEmail implements EmailerInterface, UserAwareInterface
{
    use UserAwareTrait;

    public function send(array|string $recipient, $subject, $body, $bodyText=null, $from=null)
    {
        if(!$this->transport) {
            throw new RuntimeException('Transport not set');
        }

        $message = new Message();
        if(!is_array($recipient)) {
            $recipient=[$recipient];
        }
        foreach($recipient as $r) {
            $message->addTo($r);
        }
        $from = $from ?? $this->defaultFrom ?? $this->getUser()->email;
        $message->addFrom($from);
        $message->setSender($from);
        $message->setSubject($subject);
        $message->setBody($body);
        $message->getBodyText($bodyText);

        return $this->transport->send($message);
    }

    protected $useException;
    public function setUseException(bool $bool): self
    {
        $this->useException=$bool;
        return $this;
    }

    protected $overwriteEmail;
    public function setOverwriteEmail(string $email): self
    {
        $this->overwriteEmail=$email;
        return $this;
    }

    public function setAppName(string $name): self
    {
        return $this;
    }

    public function __toString(): string
    {
        // TODO: return error string
        return '';
    }

    public function __invoke(...$data): bool
    {
        return $this->sendEmail(...$data);
    }
    public function sendEmail(array|string $recipient, string $templateId, ?array $personalisation = [], ?string $apiKey = null): bool
    {
        $template = $this->templates[$templateId] ?? $templateId;

        $from = isset($personalisation['from']) ? $personalisation['from'] : null;
        $body = $template['body'];
        $body = str_replace(array_map(function($v){return '{{'.$v.'}}';}, array_keys($personalisation)), $personalisation, $body);
        $bodyText = strip_tags($body);
        $subject = $template['subject'];
        $this->send($recipient, $subject, $body, $bodyText, $from);
        return true;
    }

    public function reportException(Exception $e, ?String $extraMessage = null, ?String $appName = null, ?String $email = null): bool
    {
        $data = [
            "app-name"=>$appName ?? 'Unkown App',
            "file"=>$e->getFile(),
            "line"=>$e->getLine(),
            "message"=>$e->getMessage(),
            "stacktrace"=>debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
        ];
        return $this->sendEmail($this->rerrorReportingEmail, $this->defaultErrorTemplate, $data);
    }
    public function reportError(array $error, ?String $recipient = null, ?String $template = null, ?String $apiKey = null, ?array $personalisation = []): bool
    {
        $data = [
            "app-name"=>$appName ?? 'Unkown App',
            "file"=>'?',
            "line"=>'?',
            "message"=>$error,
            "stacktrace"=>debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
        ];
        return $this->sendEmail($recipient??$this->rerrorReportingEmail, $template??$this->defaultErrorTemplate, $data);
    }

    protected $templates=[];
    public function setTemplates(array $templates, bool $replace = true): self
    {
        if($replace) {
            $this->templates=$templates;
            return $this;
        }
        $this->templates=array_merge($this->templates, $templates);
        return $this;
    }
    public function setTemplate(string $name, string $template): self
    {
        $this->templates[$name]=$template;
        return $this;
    }

    protected $defaultFrom;
    public function setDefaultFrom(string $email): self
    {
        $this->defaultFrom=$email;
        return $this;
    }

    protected $rerrorReportingEmail;
    public function setErrorReportingEmail(string $email): self
    {
        $this->rerrorReportingEmail=$email;
        return $this;
    }

    protected $defaultErrorTemplate = [
        'body'=>'<p style="padding-bottom:1em;">To the administrators,</p>
            <p>
            An error was detected in ((app-name)), the details are as follow:<br>
            File: ((file))<br>
            Line: ((line))<br>
            Message: ((message))<br>
            ((stacktrace))
            </p>',
        'subject'=>'Error in ((app-name))',
    ];
    public function setErrorTemplate(string $content, string $subject='Error in ((app-name))'): self
    {
        $this->defaultErrorTemplate=["body"=>$content, "subject"=>$subject];
        return $this;
    }

    protected $transport;
    public function setSmtpTransport(SmtpTransport $obj): self
    {
        $this->transport=$obj;
        return $this;
    }
}
