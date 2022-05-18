<?php
namespace Logger\Model;

use Psr\Log\LoggerTrait as PsrTrait;

trait LoggerTrait
{
    use PsrTrait;

    protected function interpolate($message, array $context = array())
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
