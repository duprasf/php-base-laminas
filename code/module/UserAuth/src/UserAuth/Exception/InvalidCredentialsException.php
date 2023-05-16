<?php
namespace UserAuth\Exception;

class InvalidCredentialsException extends UserException
{
    protected $message = 'The credentials provided did not match our records';   // exception message
}

