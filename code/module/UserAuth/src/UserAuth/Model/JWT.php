<?php

namespace UserAuth\Model;

use UserAuth\Exception\JwtExpiredException;
use UserAuth\Exception\JwtException;

class JWT
{
    private $secret;
    public function setSecret(string $secret)
    {
        $this->secret = $secret;
        return $this;
    }
    protected function getSecret()
    {
        return $this->secret;
    }

    private $issuer;
    public function setIssuer(string $iss)
    {
        $this->issuer = $iss;
        return $this;
    }
    protected function getIssuer()
    {
        return $this->issuer;
    }

    protected function getDefaultHeaders()
    {
        return [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
    }

    /**
    * Generation the JWT
    *
    * @param array $header
    * @param array $payload
    * @param int $timeToLive time to live (in secondes) 604800 = 7 days
    * @return string Token
    */
    public function generate(array $payload, int $timeToLive = 604800): string
    {
        if($timeToLive > 0) {
            // if $timeToLive is 0, use the value defined in $payload.
            // This is used to confirm the token is valid
            $now = new \DateTime();
            $payload['iss'] = $this->getIssuer();
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $payload['iat'] + $timeToLive;
            //audience
            $payload['aud'] = 'api://default';
            //subject/userId
            $payload['sub'] = $payload['userId'] ?? md5(time());
            //JSON Token ID
            $payload['jti'] = '';

        }
        $header = $this->getDefaultHeaders();

        $base64Header = $this->cleanBase64(base64_encode(json_encode($header)));
        $base64Payload = $this->cleanBase64(base64_encode(json_encode($payload)));

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, base64_encode($this->getSecret()), true);
        $base64Signature = $this->cleanBase64(base64_encode($signature));

        return $base64Header.'.'.$base64Payload.'.'.$base64Signature;
    }

    /**
    * Change char that are not allowed in JWT
    *
    * @param string $string
    * @return string
    */
    protected function cleanBase64(string $string): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], $string);
    }

    /**
    * token validation
    *
    * @param string $token Token à vérifier
    * @return bool Vérifié ou non
    */
    public function valid(string $token): bool
    {
        $header = $this->getHeader($token);
        $payload = $this->getPart($token, 1);

        $newToken = $this->generate($payload, 0);

        return $header === $this->getDefaultHeaders() && $token === $newToken;
    }

    /**
    * return the header of the token
    *
    * @param string $token Token
    * @return array Header
    */
    public function getHeader(string $token): array
    {
        return $this->getPart($token, 0);
    }

    /**
    * return the payload of the token
    *
    * @param string $token Token
    * @return array Payload
    */
    public function getPayload(?string $token)
    {
        if(!is_string($token)) {
            throw new JwtException('Token is null or not a string');
        }
        if(!$this->isToken($token)) {
            throw new JwtException('Token not found');
        }

        if(!$this->valid($token)) {
            throw new JwtException('Invalid token');
        }

        if($this->isExpired($token)) {
            throw new JwtExpiredException('Expired token');
        }

        return $this->getPart($token, 1);
    }

    protected function getPart(string $token, int $index)
    {
        $array = explode('.', $token);
        return json_decode(base64_decode($array[$index]), true);
    }

    /**
    * check if token is expired
    *
    * @param string $token
    * @return bool expired or not
    */
    public function isExpired(string $token): bool
    {
        $payload = $this->getPart($token, 1);

        $now = new \DateTime();

        return $payload['exp'] < $now->getTimestamp();
    }

    /**
    * Check if string could be a token
    *
    * @param string $token Token à vérifier
    * @return bool Vérifié ou non
    */
    public function isToken(string $token): bool
    {
        return !!preg_match(
            '(^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$)',
            $token
        );
    }
}
