<?php

namespace GcNotify;

use Exception;
use GcNotify\Exception\GcNotifyException;

/**
* Class that sends email using GcNotify (https://notification.canada.ca/)
*
* @author Francois Dupras, francois.dupras@canada.ca
* @version 1.2
*/
class GcNotify
{
    protected $lastPage;
    protected $lastStatus;
    protected $lastError;
    protected $baseUrl = 'https://api.notification.canada.ca';
    protected $port = 443;

    protected $useException = false;
    public function setUseException(bool $bool)
    {
        $this->useException = $bool;
        return $this;
    }
    protected function getUseException()
    {
        return $this->useException;
    }

    protected $errorReportingSecretKey = null;
    public function setErrorReportingKey($key)
    {
        $this->errorReportingSecretKey = $key;
        return $this;
    }

    protected $apiKey = null;
    public function setApiKey(String $key)
    {
        $this->apiKey = $key;
        return $this;
    }

    public function setBaseUrl($url)
    {
        $this->baseUrl = $url;
        return $this;
    }

    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    protected $genericErrorTemplate = 'e0b6ac22-6bac-4b76-815a-67423219a16e';
    public function setGenericErrorTemplate($template)
    {
        $this->genericErrorTemplate = $template;
        return $this;
    }

    protected $genericErrorEmail = 'imsd.web-dsgi@hc-sc.gc.ca';
    public function setGenericErrorEmail($email)
    {
        $this->genericErrorEmail = $email;
        return $this;
    }

    protected $overrideEmail;
    public function setOverrideEmail($email)
    {
        return $this->setOverwriteEmail($email);
    }
    public function setOverwriteEmail($email)
    {
        $this->overrideEmail = $email;
        return $this;
    }
    public function getOverrideEmail()
    {
        return $this->getOverwriteEmail();
    }
    public function getOverwriteEmail()
    {
        return $this->overrideEmail;
    }

    /**
    * The bridge is not setup in version PHP 7 of this class since PHP 7 should support TLS 1.2 by default
    *
    * @param mixed $bool
    * @return GcNotify
    */
    public function setUseBridge($bool)
    {
        return $this;
    }

    protected $appName;
    public function setAppName($name)
    {
        $this->appName = $name;
        return $this;
    }

    public function getAppName()
    {
        return $this->appName;
    }

    protected $templates;
    public function setTemplates(array $templates)
    {
        $this->templates = $templates;
        return $this;
    }
    public function setTemplate($name, $id)
    {
        $this->templates[$name] = $id;
        return $this;
    }
    public function getTemplates()
    {
        return $this->templates;
    }

    public function __get($name)
    {
        $return = null;
        switch($name) {
            case 'error':
            case 'lastError':
                $return = $this->lastError;
                break;
            case 'page':
            case 'lastPage':
                $return = $this->lastPage;
                break;
            case 'status':
            case 'lastStatus':
            case 'code':
            case 'lastCode':
            case 'statusCode':
            case 'lastStatusCode':
                $return = $this->lastStatus;
                break;
            default:
                break;
        }
        return $return;
    }

    public function __call($name, $arg)
    {
        $return = null;
        switch($name) {
            case 'getLastError':
                $return = $this->lastError;
                break;
            case 'getLastPage':
                $return = $this->lastPage;
                break;
            case 'getStatusCode':
            case 'getLastStatusCode':
                $return = $this->lastStatus;
                break;
            default:
                break;
        }
        return $return;
    }

    public function __toString()
    {
        $data = array(
            'error' => $this->lastError,
            'page' => $this->lastPage,
            'status' => $this->lastStatus
        );

        return json_encode($data);
    }

    public function setConfig(array $config)
    {
        $keys = ['appName', 'templates', 'apiKey'];
        foreach($keys as $key) {
            if(isset($config[$key])) {
                call_user_func([$this, 'set'.ucfirst($key)], $config[$key]);
            }
        }
        return $this;
    }

    /**
    * Can you the __invoke to call in Try/Catch or to send an normal email
    * see each functions for specific parameters
    *
    * @see GcNotify::reportException, GcNotify::reportError
    * @param mixed $data
    */
    public function __invoke(...$data)
    {
        if(!count($data)) {
            return false;
        }

        if($data[0] instanceof \Exception) {
            return $this->reportException(...$data);
        } else {
            return $this->sendEmail(...$data);
        }
    }

    /**
    * Will send the exception to the admin. If no email is specified
    * it will be sent to the default admin (should be IMSD Web)
    *
    * @param \Exception $e
    * @param string $extraMessage some extra info added before the exception
    * @param string $appName the name of the app
    * @param string $email recipient email, default ->genericErrorEmail
    *
    * @return bool true if successful false otherwise (use ->lastPage for details)
    */
    public function reportException(\Exception $e, ?String $extraMessage = null, ?String $appName = null, ?String $email = null)
    {
        $message = trim($extraMessage.PHP_EOL.$e->getMessage()).PHP_EOL;
        $previous = $e;
        while($previous = $previous->getPrevious()) {
            $message.= $previous->getMessage().' ('.basename($previous->getFile()).':'.$previous->getLine().')'.PHP_EOL;
        }
        return $this->reportError(
            [
                'message' => $message,
                'stacktrace' => preg_replace('(#(\d+))', '\1)', $e->getTraceAsString()).PHP_EOL,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'app-name' => $appName ?? $this->getAppName(),
            ],
            $email ?? $this->genericErrorEmail,
            null,
            $this->errorReportingSecretKey
        );
    }

    /**
    * Report an error in an application
    *
    * @param array $error details with [message=>, file=>, line=>, app-name]
    * @param string $recipient the email of the recipient
    * @param string $template the ID of a template if null, it will use ->genericErrorTemplate
    * @param string $apiKey the key of an application, default ->genericErrorEmail
    * @param array $personalisation pass specific template variables
    *
    * @return bool true if successful false otherwise (use ->lastPage for details)
    */
    public function reportError(array $error, ?String $recipient = null, ?String $template = null, ?String $apiKey = null, ?array $personalisation = [])
    {
        $data = [];
        if(!isset($error['message'])) {
            $error['message'] = 'No message was provided';
        }
        if(!isset($error['file'])) {
            $error['file'] = 'No file name was provided';
        }
        if(!isset($error['line'])) {
            $error['line'] = 'No line number was provided';
        }
        if(!isset($error['app-name'])) {
            $error['app-name'] = $this->appName ?? 'No application name specified';
        }
        if(!isset($error['stacktrace'])) {
            $error['stacktrace'] = '';
        }

        if(!isset($data['template_id'])) {
            $data['template_id'] = $this->genericErrorTemplate;
        }
        if(!isset($data['email_address'])) {
            $data['email_address'] = $recipient ?? $this->genericErrorEmail;
        }
        $data['personalisation'] = array_merge($error, $personalisation);
        return $this->makeRequest('/v2/notifications/email', $data, $apiKey ?? $this->errorReportingSecretKey);
    }

    /**
    * Sends an email using GC Notify
    *
    * @param String $emailRecipient the recipient of your email
    * @param String $templateId the template to use
    * @param Array $personalisation an array with the variable in the template
    * @param String $apiKey the API key if not set globally
    *
    * @return bool true if successful false otherwise (use ->lastPage for details)
    */
    public function sendEmail(string $recipient, string $templateId, ?array $personalisation = [], ?string $apiKey = null)
    {
        $data = [];
        $data['template_id'] = $this->templates[$templateId] ?? $templateId;
        $data['email_address'] = $recipient;
        $data['personalisation'] = $personalisation;

        return $this->makeRequest('/v2/notifications/email', $data, $apiKey);
    }

    /**
    * This method makes the call to GC Notify server.
    *
    * @param string $url the URL to call (email or SMS)
    * @param array $postData the data to be sent as an array
    * @param String $apiKey the API key to use, will use global API Key if not specified
    *
    * @return bool true if successful false otherwise (use ->lastPage for details)
    */
    protected function makeRequest(string $url, array $postData, ?string $apiKey = null)
    {
        if(!$apiKey && !$this->apiKey) {
            if($this->getUseException()) {
                throw new GcNotifyException('No API key set for GC Notify');
            }
            print 'No API key set for GC Notify';
            return false;
        }

        // if the override is set, make sure all emails are redirected
        if($this->getOverrideEmail()) {
            $postData['email_address'] = $this->getOverrideEmail();
        }
        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl.$url);
        // port 443 since the requests are HTTPS
        curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // return the data from the call as a variable instead of printing it to the output
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // fresh connection, just to make sure
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        // cancel if no answer in 60 seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        // pass the API Key
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: ApiKey-v1 '.($apiKey ?? $this->apiKey),
            'Content-type: application/json',
        ));
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));

        // send the post data as json string called gapiRequest
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        // execute the call, get the return page data and status code
        $page = curl_exec($ch);
        $this->lastPage = $page;
        $this->lastStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->lastError = curl_errno($ch);
        curl_close($ch);

        $success = $this->lastStatus >= 200 && $this->lastStatus <= 299;
        if($this->getUseException() && !$success) {
            throw new GcNotifyException('Status code not in the 200 '.$page);
        }
        return $success;
    }

    public function readyToSend(): bool
    {
        if($this->apiKey && $this->baseUrl && count($this->templates)) {
            return true;
        }
        return false;
    }
}
