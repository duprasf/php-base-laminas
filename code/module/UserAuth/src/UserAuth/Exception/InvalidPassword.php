<?php

namespace UserAuth\Exception;

use UserAuth\Exception\UserException;

class InvalidPassword extends UserException
{
    protected $message = 'Password in invalid. Please look at the password rules and try again';   // exception message
}
