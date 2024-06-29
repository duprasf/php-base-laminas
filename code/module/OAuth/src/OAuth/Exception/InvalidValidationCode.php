<?php

namespace OAuth\Exception;

class InvalidValidationCode extends OAuthException
{
    protected $message = 'The validation code is invalid';
}
