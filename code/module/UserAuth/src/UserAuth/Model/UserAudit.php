<?php
namespace UserAuth\Model;

use \Laminas\EventManager\SharedEventManager;
use \UserAuth\Module as UserAuth;
use \Psr\Log;

class UserAudit
{
    protected $eventManager;
    public function setEventManager(SharedEventManager $manager)
    {
        $this->eventManager = $manager;
        $manager->attach(
            '*',
            '*',
            [$this, 'listen'],
            10
        );
    }
    public function getEventManager()
    {
        return $this->eventManager;
    }

    public function listen($e)
    {
        $event='';
        $level = Log\LogLevel::INFO;
        $message = '[USER_AUTH] ';
        $context=[];
        $params = $e->getParams();
        $context['username']=$params['email']??'';
        $context['userId']=$params['userId']??'';
        $context['email']=$params['email']??'';
        $context['ip']=$_SERVER['SERVER_ADDR'];
        $context['userAgent']=$_SERVER['HTTP_USER_AGENT'];

        switch($e->getName()) {
            case UserAuth::EVENT_LOGIN:
                $message.='[LOGIN] {username} ({userId}) has login successfuly from {ip} using {userAgent}.';
                break;
            case UserAuth::EVENT_LOGIN_FAILED:
                $level = Log\LogLevel::WARNING;
                $message.='[LOGIN_FAILED] A failed login attempt for {username} ({userId}) was attempted from {ip} using {userAgent}.';
                break;
            case UserAuth::EVENT_LOGOUT:
                $message.='[LOGOUT]{username} ({userId}) logged out.';
                break;
            case UserAuth::EVENT_RESET_PASSWORD_REQUEST:
                $message.='[RESET_PASS_REQUEST] A password reset was requested for {username} ({userId}) from {ip} using {userAgent} sent to {email}.';
                break;
            case UserAuth::EVENT_RESET_PASSWORD_HANDLED:
                $message.='[RESET_PASS_HANDLED] A password reset link was used for {username} ({userId}) from {ip} using {userAgent}.';
                break;
            case UserAuth::EVENT_CONFIRM_EMAIL_HANDLED:
                $message.='[CONFIRM_EMAIL_SENT] A confirmation email was sent to {email} for user {username} ({userId}).';
                break;
            case UserAuth::EVENT_REGISTER:
                $message.='[REGISTER] A new user, {username} ({userId}), has registered from {ip} using {userAgent}.';
                break;
            case UserAuth::EVENT_REGISTER_FAILED:
                $level = Log\LogLevel::ALERT;
                $message.='[REGISTER_FAILED] A failed registration was detected with username {username} from {ip} using {userAgent}.';
                break;
            case UserAuth::EVENT_CHANGE_PASSWORD:
                $message.='[PASSWORD_CHANGED] A password was successfuly changed for {username} ({userId}) from {ip} using {userAgent}.';
                break;
            default:
                break;
        }
        if($event == '') {
            return $this;
        }

        $this->getLogger()->log($level, $message, $context);
        return $this;
    }

}

