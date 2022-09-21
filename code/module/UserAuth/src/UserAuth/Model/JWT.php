<?php
namespace UserAuth\Model;

class JWT {
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

    /**
    * Generation the JWT
    *
    * @param array $header
    * @param array $payload
    * @param int $timeToLive time to live (in secondes) 604800 = 7 days
    * @return string Token
    */
    public function generate(array $header, array $payload, int $timeToLive = 604800): string
    {
        if($timeToLive > 0){
            $now = new \DateTime();
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $payload['iat'] + $timeToLive;
        }

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

        $newToken = $this->generate($header, $payload, 0);

        return $token === $newToken;
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
    public function getPayload(string $token)
    {
        if(!$this->isToken($token)){
            throw new \Exception('["status"=>400, "message"=>"Token not found"]');
        }

        if(!$this->valid($token)){
            throw new \Exception('["status"=>403, "message"=>"Invalid token"]');
        }

        if($this->isExpired($token)){
            throw new \Exception('["status"=>403, "message"=>"Expired token"]');
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
