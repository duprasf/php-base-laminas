<?php
namespace Stockpile\Route;

use Laminas\Mvc\Router\Http\RouteInterface;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Mvc\Router\Http\RouteMatch;
use Laminas\Router\Http\Regex;

/**
* This route will look at file on the file system for one that could match
* the requested url.
*
* The path where the file-system-route can find its files is in the config file and is
* called 'FileSystemPage' and located in "view_manager' => 'template_path_stack'
*/
class FileSystemRoute extends Regex
{
    private $config;
    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this->config;
    }
    protected function getConfig()
    {
        return $this->config;
    }

    public function __construct($regex, $spec,array $defaults)
    {
        parent::__construct($regex, $spec, $defaults);
    }

    public function match(Request $request, $pathOffset = null)
    {
        $match = parent::match($request, $pathOffset);
        if (!$match) {
            return null;
        }

        // get page from file system
        $path = preg_replace('(/+$)', '', $match->getParam('path'));
        if(strpos($path,'..') !== false) return null;

        $path = parse_url($path, PHP_URL_PATH);

        $lang = $match->getParam('lang');
        $lang3 = $lang == 'fr' ? 'fra' : 'eng';


        $config = $this->getConfig();
        $root = $config['view_manager']['template_path_stack']['FileSystemPage'];

        $possibility = array(
            $path.'-'.$lang.'.phtml',
            $path.'/index-'.$lang.'.phtml',
            $path.'-'.$lang3.'.phtml',
            $path.'/index-'.$lang3.'.phtml',
            $path.'-'.$lang.'.php',
            $path.'/index-'.$lang.'.php',
            $path.'-'.$lang3.'.php',
            $path.'/index-'.$lang3.'.php',
        );
        $found = false;
        foreach($possibility as $fullpath) {
            if(file_exists($root.$fullpath)) {
                $found = true;
                break;
            }
        }
        if(!$found) {
            return null;
        }

        foreach($this->defaults as $k=>$v) {
            $match->setParam($k, $v);
        }
        $match->setParam('path', $fullpath);
        $match->setParam('directPath', $root.$fullpath);

        return $match;
    }
}