<?php
namespace Application\View\Helper;

use Laminas\View\Helper\HeadScript as OriginalHeadScript;
use Laminas\Filter\Word\CamelCaseToDash;
use Laminas\Filter\Word\DashToCamelCase;

/**
* Basic HeadScript replacement, any script will look
* for a sri file and add the integrity attribute
*/
class HeadScript extends OriginalHeadScript {
    protected $config;
    public function setSearchFolders(array $config)
    {
        $this->config = $config;
    }

    public function getSearchFolders()
    {
        return $this->config;
    }

    public function toString($indent = null)
    {
        foreach ($this as $item) {
            if (! $this->isValid($item)) {
                continue;
            }

            if(isset($item->attributes['integrity'])) {
                continue;
            }


            $camelToDash = new CamelCaseToDash();
            $dashToCamel = new DashToCamelCase();

            $config = $this->getSearchFolders();
            $originalPath = $item->attributes['src'];
            $path = preg_replace('((\.min)?\.js)', '.json', $originalPath);

            // this removes the leading / and prepare for the specific module condition
            $split = explode('/', $path, 3);
            array_shift($split);
            $path = implode('/', $split);

            if(isset($config[$split[0]])) {
                // if the user requested a specific module's asset
                // only check to serve from that one
                $config = array($config[$split[0]]);
                $path = $split[1];
            } else if(isset($config[$dashToCamel->filter($split[0])])) {
                $config = array($config[$dashToCamel->filter($split[0])]);
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
                    if($camelToDash->filter($key) == $namespaceFolder) {
                        $path = substr($path, strlen($namespaceFolder)+1);
                    }
                    $assetToLoad = $this->searchForAsset($path, $conf);
                    if($assetToLoad) {
                        break;
                    }
                }
            }

            if(!$assetToLoad) {
                continue;
            }

            $content = file_get_contents($assetToLoad);
            $json = json_decode($content, true);
            if(!isset($json[$originalPath])) {
                continue;
            }

            $sri = $json[$originalPath];
            $item->attributes['crossorigin']="anonymous";
            $item->attributes['integrity']=$sri;
        }

        return parent::toString($indent);
    }

    /**
    * Internal method that looks at all the files in the defined folders and
    * return the first match
    *
    * @param String $path, the path we are trying to match
    * @param array $config, the configuration for this folder (whitelist extension and path)
    * @return mixed
    */
    protected function searchForAsset($path, array $config)
    {
        if(!isset($config['path'])) {
            return;
        }

        $prefix = isset($config['prefix']) && $config['prefix'] ? $config['prefix'].'/' : '';
        if(is_array($config['path'])) {
            foreach($config['path'] as $realpath) {
                $filename = $this->doesAssetExists(preg_replace('(^'.$prefix.')', '', $path), $realpath);
                if($filename) {
                    return $filename;
                }
            }
            return null;
        }
        return $this->doesAssetExists(preg_replace('(^'.$prefix.')', '', $path), $config['path']);
    }

    /**
    * Simple check to see if asset exists
    *
    * @param String $urlPath
    * @param String $internalPath
    * @return bool
    */
    protected function doesAssetExists($urlPath, $internalPath)
    {
        $filename = realpath($internalPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $urlPath));
        if($filename && file_exists($filename) && strpos($filename, $internalPath) === 0) {
            return $filename;
        }
        return false;
    }
}
