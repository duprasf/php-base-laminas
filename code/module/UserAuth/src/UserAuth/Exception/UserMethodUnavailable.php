<?php
namespace UserAuth\Exception;

use UserException;

class UserMethodUnavailable extends UserException
{
    protected $message = 'Methos is not available for this user class';   // exception message
}

