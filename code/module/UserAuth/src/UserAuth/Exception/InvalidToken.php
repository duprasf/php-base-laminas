<?php

namespace UserAuth\Exception;

use UserAuth\Exception\UserException;

class InvalidToken extends UserException
{
    protected $message = 'Token in invalid.';   // exception message
}
