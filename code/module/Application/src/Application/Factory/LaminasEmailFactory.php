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
            'name'              => getExistingEnv('SMTP_NAME') ?? 'SMTP',
            'host'              => getExistingEnv('SMTP_HOST'),
            'port'              => getExistingEnv('SMTP_PORT'),
            'connection_class'  => getExistingEnv('SMTP_AUTH'),
            'connection_config' => [
                'ssl'      => getExistingEnv('SMTP_ENCRYPTION'),
                'username' => getExistingEnv('SMTP_USERNAME'),
                'password' => getExistingEnv('SMTP_PASSWORD'),
            ],
        ]);

        $transport->setOptions($options);
        $obj->setSmtpTransport($transport);

        if(getExistingEnv('SMTP_ERROR_REPORTING_EMAIL')) {
            $obj->setErrorReportingEmail(getExistingEnv('SMTP_ERROR_REPORTING_EMAIL'));
        }

        return $obj;
    }
}
