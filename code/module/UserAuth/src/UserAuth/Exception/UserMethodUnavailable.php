<?php

namespace UserAuth\Exception;

use UserAuth\Exception\UserException;

class UserMethodUnavailable extends UserException
{
    protected $message = 'Method is not available for this user class';   // exception message
}
