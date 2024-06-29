<?php

namespace OAuth\Model;

use RuntimeException;

class CodeVerifier
{
    /**
    * Get a random string and the hash value based on the algorithm provided
    *
    * @param String $algo [sha256] algorithm to use for the hash
    * @exception RuntimeException thrown when algo is not supported
    * @return array containing code, publicHash and the algo used
    */
    public static function getCodeVerifier(String $algoname = 'S256')
    {
        $bytes = random_bytes(64);
        $code = bin2hex($bytes);

        return self::hashCode($code, $algoname);
    }

    public static function validateCodeVerifier(String $code, String $hash, String $algoname = 'S256'): bool
    {
        $data = self::hashCode($code, $algoname);
        return $data['publicHash'] === $hash;
    }

    protected static function getAlgo(String $algoname): String
    {
        switch($algoname) {
            case 'S256':
                $algo = 'sha256';
                break;
            default:
                $algo = $algoname;
        }

        if(!in_array($algo, hash_algos())) {
            throw new RuntimeException('Algorithms not supported');
        }
        return $algo;
    }

    protected static function hashCode(String $code, String $algoname)
    {
        $algo = self::getAlgo($algoname);
        $hash = hash($algo, $code);
        $base = self::base64url_encode($hash);
        return [
            'code' => $code,
            'publicHash' => $base,
            'algo' => $algoname,
        ];
    }

    /**
    * Encode data to Base64URL
    * @param string $data
    * @return boolean|string
    */
    public static function base64url_encode($data)
    {
        // First of all you should encode $data to Base64 string
        $b64 = base64_encode($data);

        // Make sure you get a valid result, otherwise, return FALSE, as the base64_encode() function do
        if ($b64 === false) {
            return false;
        }

        // Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”
        $url = strtr($b64, '+/', '-_');

        // Remove padding character from the end of line and return the Base64URL result
        return rtrim($url, '=');
    }

    /**
    * Decode data from Base64URL
    * @param string $data
    * @param boolean $strict
    * @return boolean|string
    */
    public static function base64url_decode($data, $strict = false)
    {
        // Convert Base64URL to Base64 by replacing “-” with “+” and “_” with “/”
        $b64 = strtr($data, '-_', '+/');

        // Decode Base64 string and return the original data
        return base64_decode($b64, $strict);
    }
}
