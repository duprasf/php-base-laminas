<?php

namespace OAuth\Exception;

class MissingMandatoryValue extends OAuthException
{
    protected $message = 'Missing mandatory value in config';
}
