<?php

namespace CurlWrapper;

use CURLFile;
use CURLStringFile;
use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use Exception\CurlException;

class CurlWrapper
{
    private $handle;
    private $verb;
    private $payload;
    private $lastPage;
    private $lastHeaders;
    private $executed;
    private $returnPage;
    private $returnHeaders;
    private $attachedFiles;
    private $pubkey;
    private $headers;

    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';
    public const HEAD = 'HEAD';
    public const OPTIONS = 'OPTIONS';
    public const PATCH = 'PATCH';

    public function __construct(?string $url = null, ?Iterable $options = null)
    {
        $this->handle = curl_init($url);
        $this->reset();

        if($options) {
            $this->setOptions($options);
        }
    }

    public function __destruct()
    {
        if($this->handle) {
            curl_close($this->handle);
        }
    }

    public function __clone()
    {
        $this->handle = curl_copy_handle($this->handle);
    }

    public function __call($name, $arg)
    {
        if($name == 'verb') {
            $name = array_shift($arg);
        }

        switch($name) {
            case 'url':
                curl_setOpt($this->handle, CURLOPT_URL, $arg[0]);
                break;
            case self::GET:
            case self::POST:
            case self::PUT:
            case self::DELETE:
            case self::HEAD:
            case self::OPTIONS:
            case self::PATCH:
                $this->verb = $name;
                if(isset($arg[0])) {
                    $this->payload = $arg[0];
                }
                break;
            case 'getCode':
            case 'getStatus':
            case 'getHttpCode':
            case 'getResponseCode':
                return $this->getHttpResponseStatusCode();
                break;
        }
        return $this;
    }

    public function setOptions(Iterable $options)
    {
        if(!curl_setopt_array($this->handle, $options)) {
            throw new CurlException('One or more options could not be set '.$this->getLastError());
        }
        return $this;
    }

    public function setOption(int $option, $value)
    {
        if(!curl_setopt($this->handle, $option, $value)) {
            throw new CurlException('Option could not be set '.curl_error($this->handle));
        }
        if($option == CURLOPT_HEADER) {
            $this->returnHeaders = $value;
        }
        if($option == CURLOPT_RETURNTRANSFER) {
            $this->returnPage = $value;
        }
        return $this;
    }

    public function doNotReturnPage(bool $bool=false)
    {
        $this->returnPage = $bool;
        return $this;
    }

    public function encryptUsingPublicKey(string|OpenSSLAsymmetricKey|OpenSSLCertificate $pubkey)
    {
        $this->pubkey = $pubkey;
        return $this;
    }

    public function credentials($username, $password)
    {
        curl_setopt($this->handle, CURLOPT_USERPWD, "$username:$password");
        return $this;
    }

    public function credential($username, $password)
    {
        return $this->credentials($username, $password);
    }

    public function headers(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function headersJson()
    {
        return $this->headers([
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
    }

    public function addFile(string $filename, ?string $posted_filename = null, ?string $mime_type = null, ?string $forceName = null)
    {
        if(!file_exists($filename)) {
            return $this->addStringFile($filename, $posted_filename, $mime_type, $forceName);
        }
        // should add validation of location of the file...
        $cf = new CURLFile(
            $filename,
            $mime_type ?? mime_content_type($filename),
            $posted_filename ?? basename($filename)
        );
        if($forceName) {
            $this->attachedFiles[$forceName] = $cf;
        } else {
            $this->attachedFiles[] = $cf;
        }
        return $this;
    }

    public function addStringFile(string $data, ?string $posted_filename = null, ?string $mime_type = null, ?string $forceName = null)
    {
        $cf = new CURLStringFile(
            $data,
            $mime_type ?? null,
            $posted_filename ?? null
        );
        if($forceName) {
            $this->attachedFiles[$forceName] = $cf;
        } else {
            $this->attachedFiles[] = $cf;
        }
        return $this;
    }

    public function getNeedsToEncrypt()
    {
        return !!$this->pubkey;
    }

    public function exec(): bool
    {
        $includePayload = false;
        switch($this->verb) {
            case self::POST:
                curl_setopt($this->handle, CURLOPT_POST, true);
                $includePayload = true;
                break;
            case self::PUT:
            case self::PATCH:
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $this->verb);
                $includePayload = true;
                break;
            case self::DELETE:
            case self::OPTIONS:
            case self::HEAD:
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $this->verb);
                break;
            case self::GET:
            default:
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $this->verb);
                $includePayload = true;
                break;
        }

        if(count($this->attachedFiles)) {
            if($this->getNeedsToEncrypt()) {
                throw new CurlException('Encryption of attached file is not implemented at this time');
            }
            if(is_null($this->payload)) {
                $this->payload = [];
            } elseif(!is_array($this->payload)) {
                $this->payload = json_decode($this->payload, true);
            }
            if(!is_array($this->payload)) {
                throw new CurlException('The payload could not be converted to an array');
            }
            foreach($this->attachedFiles as $key => $file) {
                if($file instanceof CURLFile && !file_exists($file->getFilename())) {
                    throw new CurlException('File does not exists ', $file->getFilename());
                }
                $this->payload[is_numeric($key) && $file instanceof CURLFile ? $file->getPostFilename() : $key] = $file;
            }
            $this->headers(['Content-Type: multipart/form-data']);
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, $this->payload);
        } elseif($includePayload && $this->payload) {
            $payload = json_encode($this->payload);
            if($this->getNeedsToEncrypt()) {
                $publicKey = $this->pubkey;
                if(is_string($publicKey)) {
                    if(file_exists($publicKey)) {
                        $publicKey = file_get_contents($publicKey);
                    }
                    $publicKey = openssl_get_publickey(str_replace('\n', PHP_EOL, $publicKey));
                }

                if(!openssl_public_encrypt(
                    $payload,
                    $encrypted_data,
                    $publicKey
                )) {
                    exit(basename(__FILE__).':'.__LINE__);
                }

                $payload = base64_encode($encrypted_data);
            }
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, $payload);
        }

        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, $this->returnPage);
        curl_setopt($this->handle, CURLOPT_HEADER, $this->returnHeaders);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($this->handle, CURLOPT_ENCODING, '');

        $raw = curl_exec($this->handle);
        $this->executed = true;

        if($this->returnHeaders) {
            $this->lastHeaders = substr($raw, 0, $this->getInfo(CURLINFO_HEADER_SIZE));
            $this->lastPage = trim(substr($raw, strlen($this->lastHeaders)));
        } else {
            $this->lastPage = trim($raw);
        }

        $code = $this->getReturnCode();
        return $code >= 200 && $code <= 299;
    }

    public function getReturn()
    {
        return $this->getPage();
    }

    public function getPage(): ?string
    {
        return $this->getLastPage();
    }

    public function getLastPage(): ?string
    {
        if(!$this->executed) {
            throw new CurlException('You need to execute the CURL before fetching this information');
        }
        return $this->lastPage;
    }

    public function getReturnJson(): ?array
    {
        return $this->getPageJson();
    }

    public function asJson(): ?array
    {
        return $this->getPageJson();
    }

    public function getPageJson(): ?array
    {
        $json = json_decode($this->getPage(), true);
        return json_last_error() == JSON_ERROR_NONE ? $json : null;
    }

    public function getReturnCode()
    {
        if(!$this->executed) {
            throw new CurlException('You need to execute the CURL before fetching this information');
        }
        return $this->getInfo(CURLINFO_HTTP_CODE);
    }

    public function getHttpResponseStatusCode()
    {
        return $this->getReturnCode();
    }

    public function getRequestHeaders()
    {
        return $this->headers;
    }

    public function getRawHeaders()
    {
        if(!$this->executed) {
            throw new CurlException('You need to execute the CURL before fetching this information');
        }
        return $this->lastHeaders;
    }

    public function getHeaders()
    {
        if(!$this->executed) {
            throw new CurlException('You need to execute the CURL before fetching this information');
        }

        if(!$this->lastHeaders) {
            return false;
        }
        $lines = explode(PHP_EOL, $this->lastHeaders);
        $return = [trim(array_shift($lines))];// this is the HTTP version and response code

        foreach($lines as $line) {
            if(!trim($line)) {
                continue;
            }
            $split = explode(':', $line);
            $key = array_shift($split);
            $return[$key] = trim(implode(':', $split));
        }
        return $return;
    }

    public function getInfo(?int $opt = null)
    {
        return curl_getinfo($this->handle, $opt);
    }

    public function getUrl()
    {
        return $this->getInfo(CURLINFO_EFFECTIVE_URL);
    }

    public function reset()
    {
        curl_reset($this->handle);
        $this->verb = null;
        $this->payload = null;
        $this->lastPage = null;
        $this->lastHeaders = null;
        $this->executed = false;
        $this->returnPage = true;
        $this->returnHeaders = true;
        $this->attachedFiles = [];
        $this->headers = [];
        return $this;
    }

    public function getErrorNumber(): int
    {
        return curl_errno($this->handle);
    }

    public function getLastError(): ?string
    {
        return $this->getError();
    }

    public function getError(): ?string
    {
        return curl_error($this->handle);
    }

    public function getErrorStringFromNumber(int $errornum): ?string
    {
        return curl_strerror($errornum);
    }

    public function getVersion(): array
    {
        return curl_version();
    }
}
