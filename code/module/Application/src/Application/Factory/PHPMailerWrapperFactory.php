<?php

namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use PHPMailer\PHPMailer\PHPMailer;
use UserAuth\Model\User\User;

class PHPMailerWrapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $obj = new $requestedName();

        //Server settings
        $mailer = new PHPMailer(true);
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER; //Enable verbose debug output
        $mailer->isSMTP();
        $mailer->Host       = getExistingEnv('SMTP_HOST');
        if(getExistingEnv('SMTP_USERNAME') && getExistingEnv('SMTP_PASSWORD')) {
            $mailer->SMTPAuth   = true;
            $mailer->Username   = getExistingEnv('SMTP_USERNAME');
            $mailer->Password   = getExistingEnv('SMTP_PASSWORD');
        }
        $mailer->SMTPSecure = getExistingEnv('SMTP_ENCRYPTION');//PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port       = getExistingEnv('SMTP_PORT');

        $obj->setPhpMailer($mailer);

        return $obj;
    }
}
