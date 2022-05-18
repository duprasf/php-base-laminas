<?php
namespace UserAuth\Exception;

class UserConfirmException extends \Exception
{
    public const CODE_TOKEN_NOT_FOUND=1;
    public const CODE_TOKEN_EXPIRED=2;
    public const CODE_TOKEN_ALREADY_USED=3;
    public const CODE_USER_IS_BLOCKED=4;
    public const CODE_USER_DOES_NOT_EXISTS=5;
    public const CODE_EMAIL_ALREADY_CONFIRMED=6;
}

