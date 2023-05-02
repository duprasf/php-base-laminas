<?php
namespace UserAuth\Exception;

use UserException;

class JwtExpiredException extends JwtException
{
    protected $message = 'Your session has expired, your will need to login again.';   // exception message
}

