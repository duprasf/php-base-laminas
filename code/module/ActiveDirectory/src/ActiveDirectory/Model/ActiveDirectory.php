<?php
namespace ActiveDirectory\Model;

use Laminas\Ldap\Ldap;
use Laminas\Ldap\Filter;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Adapter\Ldap as LdapAdapter;
use Laminas\Ldap\Exception\LdapException;

class ActiveDirectory
{
    /**
    * Array of LDAP connection
    *
    * @var array
    * @internal
    */
    private $ldaps;
    /**
    * Set a list of LDAP servers to connect to
    *
    * @param array $arrayOfLdap array of Ldap objects
    * @return ActiveDirectory
    */
    public function setLdaps(array $arrayOfLdap)
    {
        $this->ldaps = $arrayOfLdap;
        return $this;
    }
    /**
    * Get all LDAPs objects
    *
    * @return array of LDAPs objects
    */
    protected function getLdaps()
    {
        return $this->ldaps;
    }
    /**
    * Generator that returns each LDAP config one by one
    *
    * @return Ldap
    */
    protected function getLdap()
    {
        foreach($this->ldaps as $ldap) {
            yield $ldap;
        }
    }

    /**
    * Check if a username exists, return true as soon as a match is found
    *
    * @param String $username
    * @return true if found in one of the LDAP, false otherwise
    */
    public function validateUsername(String $username)
    {
        foreach($this->getLdap() as $ldap) {
            try {
                $acctname = $ldap->getCanonicalAccountName($username, Ldap::ACCTNAME_FORM_DN);
                if($acctname) {
                    return $acctname;
                }
            } catch(\Exception $e){}
        }
        return false;
    }

    /**
    * Tries to bind to each LDAP using the provided account (DN) and password
    *
    * @param String $acctname
    * @param String $password
    */
    public function validateCredentials(String $acctname, String $password)
    {
        foreach($this->getLdap() as $ldap) {
            try {
                $ldap->bind($acctname, $password);
                $acctname = $ldap->getCanonicalAccountName($acctname);
                return true;
            } catch (LdapException $zle) {
                if ($zle->getCode() === LdapException::LDAP_X_DOMAIN_MISMATCH) {
                    continue;
                }
            }
        }
        return false;
    }

    /**
    * Get the user data from an email or username
    *
    * @param String $term
    * @param array $requestedFieldsMap
    * @param bool $returnRaw
    * @param bool $returnFirstElementOnly
    * @return array
    */
    public function getUserByEmailOrUsername(String $term, $requestedFieldsMap = array(), $returnRaw = false, $returnFirstElementOnly=false)
    {
        $data = $this->getByEmail($term, $requestedFieldsMap, $returnRaw, $returnFirstElementOnly);
        if($data) {
            return $data;
        }
        return $this->getByUsername($term, $requestedFieldsMap, $returnRaw, $returnFirstElementOnly);
    }

    /**
    * Synonym of getByEmail()
    *
    * @param String $email
    * @param array $requestedFieldsMap
    * @param bool $returnRaw
    * @param bool $returnFirstElementOnly
    * @return array
    */
    public function getUserByEmail(String $email, $requestedFieldsMap = array(), $returnRaw = false, $returnFirstElementOnly=false)
    {
        return $this->getByEmail($email, $requestedFieldsMap, $returnRaw);
    }

    /**
    * Get the user data from an email
    *
    * @param String $term
    * @param array $requestedFieldsMap
    * @param bool $returnRaw
    * @param bool $returnFirstElementOnly
    * @return array[]
    */
    public function getByEmail(String $email, $requestedFieldsMap = array(), $returnRaw = false, $returnFirstElementOnly=false)
    {
        $filters = array();
        if(is_array($email)) {
            foreach($email as $e) {
                $filters[] = Filter::equals('eti-emailaddress', $e);
                $filters[] = Filter::equals('mailaddress', $e);
                $filters[] = Filter::equals('mail', $e);
                $filters[] = Filter::equals('gcMessagingMail', $e);
                $filters[] = Filter::equals('userPrincipalName', $e);
            }
        }
        else {
            $filters[] = Filter::equals('eti-emailaddress', $email);
            $filters[] = Filter::equals('mailaddress', $email);
            $filters[] = Filter::equals('mail', $email);
            $filters[] = Filter::equals('gcMessagingMail', $email);
            $filters[] = Filter::equals('userPrincipalName', $email);
        }
        //$filter = "(&(!(userAccountControl:1.2.840.113556.1.4.803:=2))(|{$filter1}{$filter2}))";
        $filter = "(|".implode('',$filters).")";

        $array = $this->getByFilter($filter, $requestedFieldsMap, $returnRaw);
        if($returnFirstElementOnly) {
            return reset($array);
        }
        return $array;
    }


    /**
    * Get the user data from an username
    *
    * @param String $term
    * @param array $requestedFieldsMap
    * @param bool $returnRaw
    * @param bool $returnFirstElementOnly
    * @return array[]
    */
    public function getByUsername(String $accountName, $requestedFieldsMap = array(), $returnRaw = false, $returnFirstElementOnly=false)
    {
        $filters = array();
        if(is_array($accountName)) {
            foreach($accountName as $e) {
                $filters[] = Filter::equals('account', $e);
                $filters[] = Filter::equals('samaccountname', $e);
            }
        }
        else {
            $filters[] = Filter::equals('account', $accountName);
            $filters[] = Filter::equals('samaccountname', $accountName);
        }
        //$filter = "(&(!(userAccountControl:1.2.840.113556.1.4.803:=2))(|{$filter1}{$filter2}))";
        $filter = "(|".implode('',$filters).")";

        $array = $this->getByFilter($filter, $requestedFieldsMap, $returnRaw);
        if($returnFirstElementOnly) {
            return reset($array);
        }
        return $array;
    }

    /**
    * Get data from LDAP using the filter
    *
    * @param array $filter array of Laminas\Ldap\Filter
    * @param array $requestedFieldsMap
    * @param bool $returnRaw
    * @return array[]
    */
    protected function getByFilter($filter, $requestedFieldsMap = array(), $returnRaw = false)
    {
        foreach($this->getLdap() as $ldap) {
            $map = array('mail'=>'email',
                'eti-emailaddress'=>'email-eti', 'mailaddress'=>'email2', 'department'=>'department',
                'gcMessagingMail'=>'email-gc', 'userPrincipalName'=>'email-primary',
                'title'=>'title', 'givenname'=>'givenname', 'sn'=>'surname',
                'mobile'=>'mobile', 'telephonenumber'=>'phone', 'physicaldeliveryofficename'=>'cubicle',
                'samaccountname'=>'account', 'memberof'=>'memberof',
                'level1'=>'branch', 'level1-2'=>'branchId',
            );
            if(count($requestedFieldsMap) == 0) {
                $requestedFieldsMap = $map;
            }

            $searchOptions = array(
                'filter' => $filter,
                'baseDn' => $ldap->getOptions()['baseDn'],
                'scope'  => Ldap::SEARCH_SCOPE_SUB,
                'attributes'=>array_keys($map),
            );
            $resultsRaw = $ldap->searchEntries($searchOptions);
            if(!$resultsRaw) {
                continue;
            }

            foreach($resultsRaw as $k=>$v) {
                if(isset($resultsRaw[$k]['mail'])) {
                    if(!isset($resultsRaw[$k]['email-legacy'])) {
                        $resultsRaw[$k]['email-legacy'] = $resultsRaw[$k]['mail'];
                    }
                    if(!isset($resultsRaw[$k]['eti-emailaddress'])) {
                        $resultsRaw[$k]['eti-emailaddress'] = $resultsRaw[$k]['mail'];
                    }
                } else if(isset($resultsRaw[$k]['eti-emailaddress'])) {
                    if(!isset($resultsRaw[$k]['email-legacy'])) {
                        $resultsRaw[$k]['email-legacy'] = $resultsRaw[$k]['eti-emailaddress'];
                    }
                    if(!isset($resultsRaw[$k]['mail'])) {
                        $resultsRaw[$k]['mail'] = $resultsRaw[$k]['eti-emailaddress'];
                    }
                } else if(isset($resultsRaw[$k]['email-legacy'])) {
                    if(!isset($resultsRaw[$k]['eti-emailaddress'])) {
                        $resultsRaw[$k]['eti-emailaddress'] = $resultsRaw[$k]['email-legacy'];
                    }
                    if(!isset($resultsRaw[$k]['mail'])) {
                        $resultsRaw[$k]['mail'] = $resultsRaw[$k]['email-legacy'];
                    }
                }
            }
            break;
        }

        if($returnRaw) {
            return $resultsRaw;
        }
        $data = $this->rawAdDataToArray($resultsRaw, $requestedFieldsMap);
        return is_array($data) ? $data : array();
    }

    /**
    * Convert the LDAP array to a easier array that makes sense
    *
    * @param array $raw
    * @param array $map
    * @return array[]
    */
    protected function rawAdDataToArray(array $raw, array $map)
    {
        if(!isset($map['mail'])) {
            $map['mail'] = 'email';
        }
        $ad = array();
        foreach($raw as $cr) {
            if(isset($cr['mail'])) {
                $arr = array();
                foreach($cr as $k=>$v) {
                    if(isset($map[$k])) {
                        $k = $map[$k];
                    }
                    if($k == 'memberof') {
                        $arr[$k] = array();
                        if(is_array($v)) {
                            foreach($v as $group) {
                                $arr[$k][] = $this->getGroupName($group);
                            }
                        }
                        else {
                            $arr[$k][] = $this->getGroupName($v);
                        }
                    }
                    else {
                        $arr[$k] = is_array($v) ? reset($v) : $v;
                    }
                }
                if(isset($arr['givenname']) && (isset($arr['surname']) || isset($arr['sn']))) {
                    $arr['fullname'] = $arr['givenname'].' '.(isset($arr['surname']) ? $arr['surname'] : $arr['sn']);
                }
                if(isset($arr['account']) || isset($arr['email']) || isset($arr['email2'])) {
                    if(isset($map['id']) && isset($raw[$map['id']])) {
                        $id = $raw[$map['id']];
                    }
                    else {
                        $id = isset($arr['account']) ? $arr['account'] : ( isset($arr['email']) ? $arr['email'] : $arr['email2'] );
                    }
                    $ad[strtolower($id)] = $arr;
                    $ad[strtolower($id)]['raw'] = $raw;
                }
                else {
                    $arr['raw'] = $raw;
                    $ad[] = $arr;
                }
            }
        }
        return $ad;
    }

    /**
    * Get a group name from DN
    *
    * @param String $groupDN
    */
    protected function getGroupName($groupDN)
    {
        preg_match("(CN=((?:(?!,OU=)(?!,CN=).)*),(?:CN|OU)=)", $groupDN, $out);
        return isset($out[1]) ? $out[1] : null;
    }
}
