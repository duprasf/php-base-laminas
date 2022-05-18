<?php
namespace UserAuth\Model;

use \GcNotify\GcNotify;

interface UserInterface
{
    public function authenticate(String $username, String $password);
    public function register(String $email, String $password, ?GcNotify $notify);
    public function requestResetPassword(String $email, GcNotify $notify);
    public function handleVerifyEmailToken(String $token);
    public function validateResetPasswordToken(String $token);
    public function changePassword(String $token, String $password);
    public function validatePassword(String $password, String $confirmation=null, array $passwordRules=[]);
    public function isLoggedIn() : bool;
    public function getUserId() : ?int;
}
