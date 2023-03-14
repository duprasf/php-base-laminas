<?php
namespace UserAuth\Model;

class Auth
{
    public function __invoke($passwdData, $userid, $passwd)
    {
        return $this->authenticateHtpasswd($passwdData, $userid, $passwd);
    }

    public function authenticateHtpasswd($passwdData, $userid, $passwd)
    {
        foreach($passwdData as $line){
            if (strpos($line, $userid.':') === 0){
                $pair = explode(":", $line);
                $encryptedPasswdParts = explode('$',$pair[1]);
                $algorithm = $encryptedPasswdParts[1];
                $salt = $encryptedPasswdParts[2];
                return $this->verify($passwd, $pair[1], $encryptedPasswdParts[3], $algorithm, $salt);
            }
        }

        return false;
    }

    public function verify($password, $fullEncryptedString, $encryptedPassword='', $algo=PASSWORD_DEFAULT, $salt='')
    {
        $return = false;
        switch(strtolower($algo)) {
            case 'apr1':
                $return = ($encryptedPassword == $this->cryptApr1Md5($salt, $password));
                break;
            case '2y':
            case 'argon2i':
            case 'argon2id':
            default:
                return password_verify($password, $fullEncryptedString);
                break;
        }
        return $return;
    }

    public function encrypt($password, $algo=PASSWORD_DEFAULT, $salt='')
    {
        $cryptPassword = null;
        switch(strtolower($algo)) {
            case 'apr1':
                $cryptPassword = $this->cryptApr1Md5($salt, $password);
                break;
            case '2y':
                $cryptPassword = password_hash($passwd,PASSWORD_BCRYPT);
                break;
            case 'argon2i':
                $cryptPassword = password_hash($passwd,PASSWORD_ARGON2I);
                break;
            case 'argon2id':
                $cryptPassword = password_hash($passwd,PASSWORD_ARGON2ID);
                break;
            default:
                $cryptPassword = password_hash($passwd,PASSWORD_DEFAULT);
                break;

        }
        return $cryptPassword;
    }

    public function cryptApr1Md5($salt, $plainpasswd) {
        $len = strlen($plainpasswd);
        $text = $plainpasswd.'$apr1$'.$salt;
        $bin = pack("H32", md5($plainpasswd.$salt.$plainpasswd));
        for($i = $len; $i > 0; $i -= 16) {
            $text .= substr($bin, 0, min(16, $i));
        }
        for($i = $len; $i > 0; $i >>= 1) {
            $text .= ($i & 1) ? chr(0) : $plainpasswd[0];
        }
        $bin = pack("H32", md5($text));
        for($i = 0; $i < 1000; $i++) {
            $new = ($i & 1) ? $plainpasswd : $bin;
            if ($i % 3) {
                $new .= $salt;
            }
            if ($i % 7) {
                $new .= $plainpasswd;
            }
            $new .= ($i & 1) ? $bin : $plainpasswd;
            $bin = pack("H32", md5($new));
        }
        $tmp='';
        for ($i = 0; $i < 5; $i++) {
            $k = $i + 6;
            $j = $i + 12;
            if ($j == 16) {
                $j = 5;
            }
            $tmp = $bin[$i].$bin[$k].$bin[$j].$tmp;
        }
        $tmp = chr(0).chr(0).$bin[11].$tmp;
        $tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
            "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
            "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"
        );
        return '$apr1$'.$salt.'$'.$tmp;
    }
}
