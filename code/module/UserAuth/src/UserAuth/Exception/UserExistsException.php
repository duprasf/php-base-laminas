<?php

namespace UserAuth\Exception;

use UserAuth\Exception\UserException;

class UserExistsException extends UserException
{
    protected $message = "You are trying to register a user that already exists.";
}
