<?php
namespace UserAuth\Exception;

use UserException;

class UserForbidden extends UserException
{
    protected $message = 'You do not have access to this function or resource.';   // exception message
}

