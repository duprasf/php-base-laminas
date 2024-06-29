<?php

namespace GcDirectory\Model;

/**
* Class that search the GCdirectory (formely GEDS repository)
* @author Francois Dupras, Infrastructure Canada/ Health Canada
* @author francois.dupras@canada.ca
* @version 2.0
*/
class GcDirectory
{
    public const SEARCH_FIELD_SURNAME_GIVEN_NAME = 0;
    public const SEARCH_FIELD_SURNAME = 1;
    public const SEARCH_FIELD_GIVEN_NAME = 2;
    public const SEARCH_FIELD_PHONE = 3;
    public const SEARCH_FIELD_TITLE = 4;
    public const SEARCH_FIELD_EMAIL = 5;
    public const SEARCH_FIELD_ALL_FIELDS = 9;

    public const SEARCH_BEGINS_WITH = 0;
    public const SEARCH_ENDS_WITH = 1;
    public const SEARCH_CONTAINS = 2;
    public const SEARCH_EXACT = 3;

    private $lang;
    protected function getLang()
    {
        return $this->lang;
    }
    public function setLang($lang)
    {
        $this->lang = $lang;
        return $this;
    }

    private $config;
    protected function getConfig()
    {
        return $this->config;
    }
    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    protected $orgId = '';
    protected $authorizationId;
    protected $lastStatus;
    protected $lastPage;

    public function __get($name)
    {
        // return the last status of a call, mostly for debuging purposes
        if($name == 'status' || $name == 'lastStatus') {
            return $this->lastStatus;
        }
        return null;
    }

    /**
    * Search for a person. The term is the string that needs to be matched
    *
    * @param string $term string that needs to be matched
    * @param bool $limitToOrg if true, the search will be limited to INFC
    */
    public function searchEmployees($term, $limitToOrg = false, array $params = array())
    {
        $results = array();

        $numRecords = isset($params['numRecords'])
                && is_numeric($params['numRecords'])
                && $params['numRecords'] > 0
            ?
                $params['numRecords']
            : (
                isset($params['numReturns'])
                    && is_numeric($params['numReturns'])
                    && $params['numReturns'] > 0
                ? $params['numReturns']
            : 10
            );
        $numRecordsRequested = $numRecords;

        // add one to see if more records exists
        $numRecords++;

        $requestLang = isset($params['lang']) ? $params['lang'] : $this->getLang();

        // if $limitToOrg is true, limited the search to INFC
        $baseDn = $limitToOrg ? "ou=HC-SC, o=GC, c=CA" : "";
        $data = $this->makeRequest(
            'employees',
            [
                "searchField" => self::SEARCH_FIELD_EMAIL,
                "searchCriterion" => self::SEARCH_EXACT,
                "maxEntries" => 1,
                "returnOrganizationInformation" => "yes",
                "returnMyProfileInformation" => "no",
                "returnExtraInformation" => "no",
            ],
            $requestLang
        );
        return $data;
    }

    /**
    * This method makes the call to GEDS server. It takes care or having the
    * proper authorizationId (from the L01 call) and adds it to the post data.
    *
    * @param array $postData the data to be sent to GEDS, as an array
    * @return array $results result of the call as an array
    */
    protected function getData($url, array $requestData)
    {
        $headers = [
            "X-3scale-proxy-secret-token: ".$this->config['secret-token'],
            "Accept-Encoding: gzip, deflate, br",
            "Accept: application/json",
        ];

        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $this->config['base-url'].'/'.$url.'?'.http_build_query($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // return the data from the call as a variable instead of printing it to the output
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // execute the call, get the return page data and status code
        $page = curl_exec($ch);
        $this->lastPage = $page;
        $this->lastStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // decode the data to an array
        $returnValue = json_decode($page, true);

        return $returnValue;
    }

    /**
    * This method makes the call to GEDS server. It takes care or having the
    * proper authorizationId (from the L01 call) and adds it to the post data.
    *
    * @param array $postData the data to be sent to GEDS, as an array
    * @return array $results result of the call as an array
    */
    protected function postData($url, array $requestData)
    {
        return 'not implemented';
        $headers = [
            "X-3scale-proxy-secret-token: ".$this->config['secret-token'],
            "Accept-Encoding: gzip, deflate, br",
            "Accept: application/json",
        ];

        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $this->config['base-url'].'/'.$url.'?'.http_build_query($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // port 443 since the requests are HTTPS
        curl_setopt($ch, CURLOPT_PORT, 443);
        // return the data from the call as a variable instead of printing it to the output
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // fresh connection, just to make sure
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        // cancel if no answer in 30 seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        // set the certificate file
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // this should be set to 2 for added security but unfortunately since
        // their certificate is not setup for the dev domain. it should work
        // (using 2) on prod with the "api.geds-sage.gc.ca" domain name
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // send the post data as json string called gapiRequest
        //curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // execute the call, get the return page data and status code
        $page = curl_exec($ch);
        $this->lastPage = $page;
        $this->lastStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // decode the data to an array
        $returnValue = json_decode($page, true);

        return $returnValue;
    }
}
