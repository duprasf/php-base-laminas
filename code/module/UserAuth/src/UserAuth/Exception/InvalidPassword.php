<?php
namespace UserAuth\Exception;

use UserException;

class InvalidPassword extends UserException
{
    protected $message = 'Password in invalid. Please look at the password rules and try again';   // exception message
}

