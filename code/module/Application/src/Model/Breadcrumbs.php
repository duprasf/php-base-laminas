<?php
namespace Application\Model;

/**
* Simple ArrayObject to handle the Breadcrumbs of a page
*/
class Breadcrumbs extends \ArrayObject
{
    protected $hidden = false;

    public function __invoke($breadcrumbs=null) {
        if($breadcrumbs != null) {
            $this->exchangeArray(array());
            $this->addBreadcrumbs($breadcrumbs);
        }
        return $this;
    }

    public function addBreadcrumbs($breadcrumbs)
    {
        $extraBreadcrumbs = array();
        if(is_array($breadcrumbs)) {
            if(isset($breadcrumbs[0]) && isset($breadcrumbs[0]['href'])) {
                $extraBreadcrumbs = $breadcrumbs;
            }
            elseif(isset($breadcrumbs['href']) && isset($breadcrumbs['title'])) {
                $extraBreadcrumbs[] = $breadcrumbs;
            }
            else {
                foreach($breadcrumbs as $href=>$name) {
                    $extraBreadcrumbs[] = array("href"=>$href, "title"=>$name);
                }
            }
        }
        else {
            if(preg_match_all('(\(([^\|]*)\|([^\)]*)\)\s*)', $data['breadcrumbs'], $out, PREG_SET_ORDER)) {
                foreach($out as $crumb) {
                    $extraBreadcrumbs[] = array('href'=>$crumb[2], 'title'=>$crumb[1]);
                }
            }
        }

        foreach($extraBreadcrumbs as $crumb) {
            // need to append each one by one because the \ArrayObject
            // does not support adding a bunch at once (merge)
            $this->append($crumb);
        }
    }

    public function tojson()
    {
        $breadcrumbs = $this->getArrayCopy();
        return json_encode($breadcrumbs);
    }
}
