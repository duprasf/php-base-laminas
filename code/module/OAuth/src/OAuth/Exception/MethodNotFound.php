<?php

namespace OAuth\Exception;

class MethodNotFound extends OAuthException
{
    protected $message = 'Specified OAuth method not found';
}
