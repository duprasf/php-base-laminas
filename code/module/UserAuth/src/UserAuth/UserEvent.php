<?php
namespace UserAuth;

class UserEvent
{
    public const REGISTER='user.register';
    public const REGISTER_FAILED='user.register_failed';
    public const LOGIN='user.login';
    public const LOGOUT='user.logout';
    public const LOGIN_FAILED='user.login_failed';
    public const RESET_PASSWORD_REQUEST='user.reset_pwd_request';
    public const RESET_PASSWORD_HANDLED='user.reset_pwd_handled';
    public const CONFIRM_EMAIL_HANDLED='user.confirm_email_handled';
    public const EMAIL_SENT='user.email_sent';
    public const CHANGE_PASSWORD='user.change_password';
}
