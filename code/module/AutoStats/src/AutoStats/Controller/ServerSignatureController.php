<?php

namespace AutoStats\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use AutoStats\Model\ServerSignature;

class ServerSignatureController extends AbstractRestfulController
{
    private $apmPubKey;
    public function setApmLitePublicKey(string $key)
    {
        $this->apmPubKey = str_replace('\n', PHP_EOL, $key);
        return $this;
    }
    protected function getApmLitePublicKey()
    {
        return $this->apmPubKey;
    }

    public function options()
    {
        return $this->setResponseHeaders($this->getResponse());
    }

    // Action used for GET requests without resource Id
    public function getList()
    {
        $view = new JsonModel();
        if(!$this->getApmLitePublicKey()) {
            $this->response->setStatusCode(500);
            $view->setVariable('error', 'Could not find public key');
            return $view;
        }

        $data = ServerSignature::get();

        $publicKey = openssl_get_publickey($this->getApmLitePublicKey());

        if(!openssl_public_encrypt(
            json_encode($data),
            $encrypted_data,
            $publicKey
        )) {
            $this->response->setStatusCode(500);
            $view->setVariable('error', 'Could not encrypt');
            return $view;
        }

        $base64 = base64_encode($encrypted_data);
        $view->setVariable('encryptedSignature', $base64);

        return $view;
    }
}
