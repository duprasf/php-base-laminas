<?php
namespace OAuth\Exception;

class InvalidClientSecret extends OAuthException
{
    protected $message = 'Invalid client secret';
}
