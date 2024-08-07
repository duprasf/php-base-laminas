<?php

namespace Stockpile\Route;

use Laminas\Mvc\Router\Http\RouteInterface;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Mvc\Router\Http\RouteMatch;
use Laminas\Router\Http\Regex;
use Stockpile\Model\MovedPage;

/**
* This route will look into a DB for pages that were moved. This can also be used as a URL shortner.
*/
class MovedPageRoute extends Regex
{
    private $movedPageObj;
    public function setMovedPageObj(MovedPage $obj)
    {
        $this->movedPageObj = $obj;
        return $this;
    }
    protected function getMovedPageObj()
    {
        return $this->movedPageObj;
    }

    public function __construct($regex, $spec)
    {
        parent::__construct($regex, $spec);
    }

    /**
    * Try to match the requested page using the MovedPageObj
    *
    * @param Request $request
    * @param mixed $pathOffset
    *
    * @return \Laminas\Router\RouteMatch|null
    */
    public function match(Request $request, $pathOffset = null)
    {
        $match = parent::match($request, $pathOffset);
        if (!$match) {
            return null;
        }

        $path = $match->getParam('path');
        if(isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
            $path .= '?'.$_SERVER['QUERY_STRING'];
        }


        $data = $this->getMovedPageObj()->match($path);
        if($data) {
            foreach($this->defaults as $k => $v) {
                $match->setParam($k, $v);
            }
            foreach($data as $k => $v) {
                $match->setParam($k, $v);
            }

            return $match;
        }
        return null;
    }
}
