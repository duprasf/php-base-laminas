<?php
namespace OAuth\Model;

use PDO;
use InvalidArgumentException;
use Laminas\Session\Container;
use Void\UUID;
use UserAuth\Model\UserInterface;
use UserAuth\Model\JWT;
use OAuth\Exception\MethodNotFound;
use OAuth\Exception\AuthorizationExpired;
use OAuth\Exception\InvalidClientSecret;
use OAuth\Exception\InvalidValidationCode;
use OAuth\Exception\ClientRedirectMismatch;
use OAuth\Exception\InvalidScope;

class OAuth2Server implements OAuth2ServerInterface
{
    private $pdo;
    public function setDb(PDO $db)
    {
        $this->pdo = $db;
        $this->garbageCollection();
        return $this;
    }
    protected function getDb()
    {
        return $this->pdo;
    }

    private $jwt;
    public function setJwt(JWT $obj)
    {
        $this->jwt = $obj;
        return $this;
    }
    protected function getJwt()
    {
        return $this->jwt;
    }

    private $ttl=[];
    public function setTTL(String $name, int $ttl)
    {
        if($name != 'token' && $name != 'refresh') {
            throw new InvalidArgumentException('unexpected TTL name, only token and refresh are accepted');
        }
        $this->ttl[$name] = $ttl;
        return $this;
    }
    public function getTTL(String $name)
    {
        return $this->ttl[$name] ?? throw new InvalidArgumentException('TTL name not set, check your factory');
    }

    public function verifyCodeVerifier(String $algoname='S256')
    {
    }

    public function verifyClient(String $client_id, String $redirect_uri, array $scope, String $lang)
    {
        $pdo = $this->getDb();
        $prepared = $pdo->prepare("
            SELECT clientId, name AS client
            FROM oauth_client
                INNER JOIN oauth_client_redirect_uri USING(clientId)
            WHERE client_id = ?
                AND redirect_uri LIKE ?
        ");

        $prepared->execute([$client_id, $redirect_uri]);
        $data = $prepared->fetch(PDO::FETCH_ASSOC);
        if(!$data) {
            throw new ClientRedirectMismatch();
        }

        $prepared = $pdo->prepare("
            SELECT scopeId, name AS scope, reason
            FROM oauth_client_scope
                INNER JOIN oauth_scope USING(scopeId, lang)
            WHERE clientId = ?
                AND clean_name LIKE ?
                AND lang LIKE ?
        ");
        $scopeReason = [];
        foreach($scope as $scopeName) {
            $prepared->execute([$data['clientId'], strtolower($scopeName), $lang]);
            $cr = $prepared->fetch(PDO::FETCH_ASSOC);
            if(!$cr) {
                throw new InvalidScope();
            }
            $scopeReason[] = $cr;
        }

        $data['scope']=$scopeReason;
        return $data;
    }

    public function getAuthorizationCode(int $clientId, String $user_id, String $redirect_uri, array $scope, String $code_challenge, String $code_challenge_method, array $payload)
    {
        $uuid = UUID::v4();
        $db = $this->getDb();

        $prepared = $db->prepare("
            INSERT INTO oauth_authorization_code
            SET
                authorization_code = :uuid,
                clientId = :clientId,
                user_id = :user_id,
                redirect_uri = :redirect_uri,
                expires = :expires,
                scope = :scope,
                id_token = :id_token,
                code_challenge = :code_challenge,
                code_challenge_method = :code_challenge_method,
                payload = :payload
        ");
        $data = [
            'uuid'=>$uuid,
            'clientId'=>$clientId,
            'user_id'=>$user_id,
            'redirect_uri'=>$redirect_uri,
            'expires'=>date('Y-m-d H:i:s', time()+60),
            'scope'=>json_encode($scope),
            'id_token'=>'',
            'code_challenge'=>$code_challenge,
            'code_challenge_method'=>$code_challenge_method,
            'payload'=>json_encode($payload),
        ];

        $prepared->execute($data);

        return $data['uuid'];
    }

    public function getToken(String $code, String $redirect_uri, String $code_verifier, String $client_id, String $client_secret)
    {
        $db = $this->getDb();
        $prepared = $db->prepare("SELECT clientId, client_secret, user_id, code_challenge, code_challenge_method, scope, expires, payload
            FROM oauth_authorization_code
                INNER JOIN oauth_client USING(clientId)
            WHERE authorization_code = ?"
        );
        $prepared->execute([$code]);
        $data = $prepared->fetch(PDO::FETCH_ASSOC);
        if(!$data) {
            throw new InvalidArgumentException('Code not found');
        }

        if(strtotime($data['expires']) < time()) {
            throw new AuthorizationExpired();
        }

        if(!password_verify($client_secret, $data['client_secret'])) {
            throw new InvalidClientSecret();
        }

        if(!CodeVerifier::validateCodeVerifier($code_verifier, $data['code_challenge'], $data['code_challenge_method'])) {
            throw new InvalidValidationCode();
        }

        $payload = json_decode($data['payload'], true);
        $payload['client_id']=$client_id;
        $payload['user_id']=$data['user_id'];
        $payload['scope']=$data['scope'];

        $insertToken = $db->prepare("
            INSERT INTO oauth_access_token
            SET access_token=:token,
                clientId=:clientId,
                user_id=:user_id,
                expires=:expires,
                scope=:scope
        ");

        $insertRefresh = $db->prepare("
            INSERT INTO oauth_refresh_token
            SET refresh_token=:token,
                clientId=:clientId,
                user_id=:user_id,
                expires=:expires,
                scope=:scope
        ");

        try {

            $db->beginTransaction();
            $token = $this->getJwt()->generate($payload, $this->getTTL('token'));
            $refreshToken = $this->getJwt()->generate($payload, $this->getTTL('refresh'));

            $saveTokenData = [
                "token"=>$token,
                "clientId"=>$data['clientId'],
                "user_id"=>$data['user_id'],
                "expires"=>date('Y-m-d H:i:s', time()+$this->getTTL('token')),
                "scope"=>$data['scope'],
            ];
            $insertToken->execute($saveTokenData);

            $saveTokenData['token'] = $refreshToken;
            $saveTokenData['expires'] = date('Y-m-d H:i:s', time()+$this->getTTL('refresh'));
            $insertRefresh->execute($saveTokenData);

            $prepareDelete = $db->prepare("DELETE FROM oauth_authorization_code WHERE authorization_code = ?");
            $prepareDelete->execute([$code]);

            $db->commit();

            return [
                "token_type"=>"Bearer",
                "access_token"=>$token,
                "expires_in"=>$this->getTTL('token'),
                "scope"=>$data['scope'],
                "refresh_token"=>$refreshToken,
            ];
        } catch (\PDOException $e) {
            $db->rollBack();
            throw new \Exception('could not save data');
        } catch (\Exception $e) {
            $db->rollBack();
            throw new \Exception('unknown error');
        }
    }

    protected function garbageCollection()
    {
        $db = $this->getDb();
        // delete everything that has expired 5 days ago
        $time = date('Y-m-d H:i:s', time()-432000);
        $db->exec("DELETE FROM oauth_access_token WHERE expires < '{$time}'");
        $db->exec("DELETE FROM oauth_authorization_code WHERE expires < '{$time}'");
        $db->exec("DELETE FROM oauth_refresh_token WHERE expires < '{$time}'");
    }
}
