<?php
namespace OAuth\Exception;

class InvalidScope extends OAuthException
{
    protected $message = 'Invalid scope requested';
}
