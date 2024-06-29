<?php

namespace UserAuth\Exception;

class JwtExpiredException extends JwtException
{
    protected $message = 'Your session has expired, your will need to login again.';   // exception message
}
