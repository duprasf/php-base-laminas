<?php
namespace OAuth\Exception;

class AuthorizationExpired extends OAuthException
{
    protected $message = 'The authorization code has expired';
}
