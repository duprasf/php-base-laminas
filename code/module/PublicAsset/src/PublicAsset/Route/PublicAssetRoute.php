<?php
namespace PublicAsset\Route;

use Laminas\Mvc\Router\Http\RouteInterface;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Mvc\Router\Http\RouteMatch;
use Laminas\Router\Http\Regex;


class PublicAssetRoute extends Regex {
    protected $config;
    public function setSearchFolders(array $config)
    {
        $this->config = $config;
    }

    public function getSearchFolders()
    {
        return $this->config;
    }

    public function match(Request $request, $pathOffset = null)
    {
        $match = parent::match($request, $pathOffset);
        if (!$match) {
            return null;
        }

        $path = preg_replace('(/+$)', '', $match->getParam('path'));
        if(strpos($path,'..') !== false) {
            return null;
        }

        $config = $this->getSearchFolders();

        // this removes the leading / and prepare for the specific module condition
        $split = explode('/', $path, 3);
        array_shift($split);
        $path = implode('/', $split);

        if(isset($config[$split[0]])) {
            // if the user requested a specific module's asset
            // only check to serve from that one
            $config = array($config[$split[0]]);
            $path = $split[1];
        }

        $assetToLoad=null;
        foreach($config as $conf) {
            $assetToLoad = $this->searchForAsset($path, $conf);
            if($assetToLoad) {
                break;
            }
        }

        if(!$assetToLoad) {
            foreach($config as $key=>$conf) {
                $namespaceFolder = substr($path, 0, strpos($path, '/'));
                if(\Void\StringFunction::camel2dashed($key) == $namespaceFolder) {
                    $path = substr($path, strlen($namespaceFolder)+1);
                }
                $assetToLoad = $this->searchForAsset($path, $conf);
                if($assetToLoad) {
                    break;
                }
            }
        }

        if(!$assetToLoad) {
            return null;
        }
        foreach($this->defaults as $k=>$v) $match->setParam($k, $v);
        $match->setParam('assetToLoad', $assetToLoad);
        //$match->setParam('directPath', $root.$fullpath);

        return $match;
    }

    protected function searchForAsset($path, array $config)
    {
        if(!isset($config['path'])) {
            return;
        }
        if(isset($config['whitelist'])) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if(
                (
                    is_string($config['whitelist'])
                    && $ext !== $config['whitelist']
                ) || (
                    is_array($config['whitelist'])
                    && !in_array($ext, $config['whitelist'])
                )
            ) {
                return null;
            }
        }

        if(is_array($config['path'])) {
            foreach($config['path'] as $realpath) {
                $filename = $this->doesAssetExists($path, $realpath);
                if($filename) {
                    return $filename;
                }
            }
        }
        else {
            return $this->doesAssetExists($path, $config['path']);
        }
    }

    protected function doesAssetExists($urlPath, $internalPath)
    {
        $filename = realpath($internalPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $urlPath));
        if($filename && file_exists($filename) && strpos($filename, $internalPath) === 0) {
            return $filename;
        }
        return false;
    }
}