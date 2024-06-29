<?php

namespace UserAuth\Exception;

use UserException;

class InvalidConfirmationPassword extends InvalidPassword
{
    protected $message = 'Password and confirmation does not match.';   // exception message
}
