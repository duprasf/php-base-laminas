<?php

namespace GcDirectory\Model;

use CurlWrapper\CurlWrapper;
use CurlWrapper\Exception\CurlException;
use Application\Trait\LanguageAwareTrait;

/**
* Class that search the GCdirectory (formely GEDS repository)
* @author Francois Dupras, Health Canada
* @author francois.dupras@canada.ca
* @version 2.0
*/
class GcDirectory
{
    use LanguageAwareTrait;

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

    public const URL_EMPLOYEES='employees';

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
    public function searchEmployees($term, $limitToOrg = false, array $params = array(), $searchField = self::SEARCH_FIELD_EMAIL, $searchCriterion=self::SEARCH_EXACT)
    {
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

        // add one to see if more records exists
        $numRecords++;

        $requestLang = isset($params['lang']) ? $params['lang'] : $this->getLang();

        // if $limitToOrg is true, limited the search to HC
        //$baseDn = $limitToOrg ? "ou=HC-SC, o=GC, c=CA" : "";
        $data = $this->getData(
            self::URL_EMPLOYEES,
            [
                "searchValue" => $term,
                "searchField" => $searchField,
                "searchCriterion" => $searchCriterion,
                "maxEntries" => 1,
                "returnOrganizationInformation" => "yes",
                "returnMyProfileInformation" => "no",
                "returnExtraInformation" => "no",
            ]
        );
        return $data;
    }

    protected function getHeaders()
    {
        $headers = [
            "X-3scale-proxy-secret-token: ".$this->config['secret-token'],
            "Accept-Encoding: gzip, deflate, br",
            "Accept: application/json",
        ];
        if(isset($this->config['adminKey'])
            && $this->config['adminKey']
            && isset($this->config['deptId'])
            && $this->config['deptId']
        ) {
            $headers[] = "adminKey: ".$this->config['adminKey'];
            $headers[] = "deptId: ".$this->config['deptId'];
        }
        return $headers;
    }

    /**
    * This method makes the call to GEDS server.
    *
    * @param array $postData the data to be sent to GEDS, as an array
    * @return array $results result of the call as an array
    */
    protected function getData($url, array $requestData)
    {
        $curl = new CurlWrapper();
        $curl
            ->url($this->config['base-url'].'/'.$url.'?'.http_build_query($requestData))
            ->headers($this->getHeaders())
            ->get()
            ->exec()
        ;

        $this->lastPage = $curl->getLastPage();
        $this->lastStatus = $curl->getReturnCode();
        return $curl->getPageJson();
    }



    /**
    * This method makes the call to GEDS server. It takes care or having the
    * proper authorizationId (from the L01 call) and adds it to the post data.
    *
    * @param array $postData the data to be sent to GEDS, as an array
    * @return array $results result of the call as an array
    */
    protected function updateData(string $url, array $data, string $verb=CurlWrapper::PUT)
    {
        if($verb != CurlWrapper::POST
            || $verb != CurlWrapper::PUT
            || $verb != CurlWrapper::DELETE
            || $verb != CurlWrapper::PATCH
        ) {
            throw new CurlException('Verb is not acceptable, please use an acceptable verb (post, put, patch or delete)');
        }
        $curl = new CurlWrapper();
        $curl
            ->url($this->config['base-url'].'/'.$url)
            ->headers($this->getHeaders())
        ;
        $curl->__class($verb, $data);

        $curl->exec();

        $this->lastPage = $curl->getLastPage();
        $this->lastStatus = $curl->getReturnCode();
        return $curl->getPageJson();
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
}
