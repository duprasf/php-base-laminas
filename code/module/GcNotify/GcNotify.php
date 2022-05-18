<?php
namespace GcNotify;

/**
* Class that sends email using GcNotify (https://notification.canada.ca/)
* This is the Version 7.1+ of PHP, for verions lower than that see GcNotify_PHP5.php
*
* @author Francois Dupras, francois.dupras@canada.ca
* @version 1.0
*/
class GcNotify {
    protected $lastPage;
    protected $lastStatus;
    protected $lastError;
    protected $baseUrl = 'https://api.notification.canada.ca';
    protected $port = 443;

    protected $errorReportingSecretKey = null;
    public function setErrorReportingKey($key) {
        preg_match('([0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$)', $key, $out);
        $this->errorReportingSecretKey = $out[0] ?? null;
        return $this;
    }

    protected $apiKey = null;
    protected $apiSecretKey = null;
    public function setApiKey(String $key)
    {
        $this->apiKey=$key;
        $this->apiSecretKey = $this->extractApiKey($key);
        return $this;
    }

    public function extractApiKey(String $api)
    {
        preg_match('([0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$)', $api, $out);
        return isset($out[0]) ? $out[0] : null;
    }

    public function setBaseUrl($url) {
        $this->baseUrl = $url;
        return $this;
    }

    public function setPort($port) {
        $this->port = $port;
        return $this;
    }

    protected $genericErrorTemplate;
    public function setGenericErrorTemplate($template) {
        $this->genericErrorTemplate = $template;
        return $this;
    }

    protected $genericErrorEmail;
    public function setGenericErrorEmail($email) {
        $this->genericErrorEmail = $email;
        return $this;
    }

    protected $appName;
    public function setAppName($name) {
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
        $this->templates=$templates;
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

    public function __toString()
    {
        $data = array(
            'error'=>$this->lastError,
            'page'=>$this->lastPage,
            'status'=>$this->lastStatus
        );

        return json_encode($data);
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

        if($data[0] instanceOf \Exception) {
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
    * @param string $appName the name of the app
    * @param string $email recipient email, default ->genericErrorEmail
    *
    * @return bool true if successful false otherwise (use ->lastPage for details)
    */
    public function reportException(\Exception $e, ?String $appName=null, ?String $email = null)
    {
        return $this->reportError([
            'message'=>$e->getMessage(),
            'file'=>$e->getFile(),
            'line'=>$e->getLine(),
            'app-name'=>$appName ?? $this->getAppName(),
        ], $email, null, $this->errorReportingSecretKey);
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
    public function reportError(array $error, ?String $recipient=null, ?String $template=null, ?String $apiKey=null, ?array $personalisation=[])
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
    public function sendEmail(String $recipient, String $templateId, ?array $personalisation=[], ?String $apiKey=null)
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
    protected function makeRequest(String $url, array $postData, ?String $apiKey = null)
    {
        if(!$apiKey && !$this->apiSecretKey) {
            print 'No API key set for GC Notify';
            return false;
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
            'Authorization: ApiKey-v1 '.$this->extractApiKey($apiKey ?? $this->apiSecretKey),
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

        return $this->lastStatus >= 200 && $this->lastStatus <= 299;
    }
}
