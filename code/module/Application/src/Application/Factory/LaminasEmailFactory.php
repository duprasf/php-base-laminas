<?php

namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;

class LaminasEmailFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $obj = new $requestedName();

        $transport = new SmtpTransport();
        $options   = new SmtpOptions([
            'name'              => getenv('SMTP_NAME') ?? 'SMTP',
            'host'              => getenv('SMTP_HOST'),
            'port'              => getenv('SMTP_PORT'),
            'connection_class'  => getenv('SMTP_AUTH'),
            'connection_config' => [
                'ssl'      => getenv('SMTP_ENCRYPTION'),
                'username' => getenv('SMTP_USERNAME'),
                'password' => getenv('SMTP_PASSWORD'),
            ],
        ]);

        $transport->setOptions($options);
        $obj->setSmtpTransport($transport);

        if(getenv('SMTP_ERROR_REPORTING_EMAIL')) {
            $obj->setErrorReportingEmail(getenv('SMTP_ERROR_REPORTING_EMAIL'));
        }

        return $obj;
    }
}
