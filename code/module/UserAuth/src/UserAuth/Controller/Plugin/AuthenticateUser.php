<?php

namespace UserAuth\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Http\Header\Authorization;
use Laminas\Http\Header\GenericHeader;
use UserAuth\Model\User\UserInterface;
use UserAuth\Model\EmailUser;
use UserAuth\Model\JWT;
use UserAuth\Exception\JwtException;

class AuthenticateUser extends AbstractPlugin
{
    private $user;
    public function setUser(UserInterface $obj)
    {
        $this->user = $obj;
        return $this;
    }
    protected function getUser()
    {
        return $this->user;
    }

    private $jwtObj;
    public function setJwtObj(JWT $obj)
    {
        $this->jwtObj = $obj;
        return $this;
    }
    protected function getJwtObj()
    {
        return $this->jwtObj;
    }

    public function __invoke(Authorization|GenericHeader|string|null $auth): string|UserInterface
    {
        $jwt = null;
        if($auth instanceof Authorization) {
            $jwt = str_replace('Bearer ', '', $auth->getFieldValue());
        }
        if($auth instanceof GenericHeader) {
            $jwt = $auth->getFieldValue();
            if($jwt === "null") {
                $jwt = null;
            }
        }
        if(is_string($auth)) {
            $jwt = $auth;
        }

        if(!$jwt) {
            throw new JwtException('No JWT found');
        }
        
        if(!$this->getUser()) {
            return $this->getJwtObj()->getPayload($jwt);
        }

        $this->getUser()->loadFromJwt($jwt);
        return $this->getUser();
    }
}
