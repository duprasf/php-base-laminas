<?php

namespace OAuth\Exception;

class ClientRedirectMismatch extends OAuthException
{
    protected $message = 'Client and redirect URL are not matching';
}
