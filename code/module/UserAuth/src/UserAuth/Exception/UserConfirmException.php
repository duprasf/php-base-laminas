<?php
namespace UserAuth\Exception;

use UserException;

class UserConfirmException extends UserException
{
    public const CODE_INVALID_TOKEN=1;
    public const CODE_USER_IS_BLOCKED=4;
    public const CODE_USER_DOES_NOT_EXISTS=5;
    public const CODE_EMAIL_ALREADY_CONFIRMED=6;
}

