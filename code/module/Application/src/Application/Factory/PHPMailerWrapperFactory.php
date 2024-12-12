<?php

namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use PHPMailer\PHPMailer\PHPMailer;

class PHPMailerWrapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $obj = new $requestedName();
        
        //Server settings
        $mailer = new PHPMailer(true);
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER; //Enable verbose debug output
        $mailer->isSMTP();
        $mailer->Host       = getenv('SMTP_HOST');
        if(getenv('SMTP_USERNAME') && getenv('SMTP_PASSWORD')) {
            $mailer->SMTPAuth   = true;
            $mailer->Username   = getenv('SMTP_USERNAME');
            $mailer->Password   = getenv('SMTP_PASSWORD');
        }
        $mailer->SMTPSecure = getenv('SMTP_ENCRYPTION');//PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port       = getenv('SMTP_PORT');

        $obj->setPhpMailer($mailer);
        
        return $obj;
    }
}
