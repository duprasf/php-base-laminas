<?php
namespace UserAuth\Exception;

use UserAuth\Exception\UserException;

class UserForbidden extends UserException
{
    protected $message = 'You do not have access to this function or resource.';   // exception message
}

