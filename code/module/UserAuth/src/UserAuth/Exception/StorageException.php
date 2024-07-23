<?php

namespace UserAuth\Exception;

use Exception;

class StorageException extends Exception
{
    protected $message = 'There was a problem with the user storage';   // exception message
}
