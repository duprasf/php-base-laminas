<?php

namespace Application\Model;

use ArrayObject;
use JsonSerializable;

/**
* Simple ArrayObject to handle the Breadcrumbs of a page
*/
class Breadcrumbs extends ArrayObject implements JsonSerializable
{
    protected $hidden = false;

    public function __invoke($breadcrumbs = null): self
    {
        if($breadcrumbs != null) {
            $this->exchangeArray(array());
            $this->addBreadcrumbs($breadcrumbs);
        }
        return $this;
    }

    public function addBreadcrumbs($breadcrumbs): self
    {
        $extraBreadcrumbs = array();
        if(is_array($breadcrumbs)) {
            if(isset($breadcrumbs[0]) && isset($breadcrumbs[0]['href'])) {
                $extraBreadcrumbs = $breadcrumbs;
            } elseif(isset($breadcrumbs['href']) && isset($breadcrumbs['title'])) {
                $extraBreadcrumbs[] = $breadcrumbs;
            } else {
                foreach($breadcrumbs as $href => $name) {
                    $extraBreadcrumbs[] = array("href" => $href, "title" => $name);
                }
            }
        } else {
            if(preg_match_all('(\(([^\|]*)\|([^\)]*)\)\s*)', $breadcrumbs, $out, PREG_SET_ORDER)) {
                foreach($out as $crumb) {
                    $extraBreadcrumbs[] = array('href' => $crumb[2], 'title' => $crumb[1]);
                }
            }
        }

        foreach($extraBreadcrumbs as $crumb) {
            // need to append each one by one because the \ArrayObject
            // does not support adding a bunch at once (merge)
            $this->append($crumb);
        }
        return $this;
    }

    public function tojson()
    {
        return json_encode($this);
    }

    public function jsonSerialize(): mixed
    {
        return $this->getArrayCopy();
    }

    /**
    * Builds a list of breadcrumbs from the filesystem.
    *
    * @param string $path The path to search for _crumb-en.menu files.
    * @return array An array of menu items, where each item is an array with two elements:
    *               the first element is the URL, and the second element is the label.
    */
    public function buildFromFilesystem(string $path): array
    {
        // Check if the path is a subfolder of the root path
        if (strpos($path, $this->rootPath) !== 0) {
            return false;
        }

        $menuItems = [];
        // Traverse the directory tree from the given $path up to the $rootPath
        while (strpos($path, $this->rootPath)===0) {
            $menuFile = $path . '/_crumb-'.$this->getLang().'.menu';

            // Check if the _crumb-en.menu file exists
            if (!file_exists($menuFile)) {
                $path = dirname($path);
                continue;
            }

            // Read the contents of the _crumb-en.menu file
            $contents = file_get_contents($menuFile);

            // Apply the regular expression pattern to extract the URL and label
            if (preg_match_all('(href="([^"]*)".*?>(.*?)</a>)', $contents, $matches, PREG_SET_ORDER)) {
                // Add the extracted menu items to the $menuItems array
                foreach ($matches as $crumb) {
                    $menuItems[$crumb[1]] = $crumb[2];
                }
            }

            // Move up one directory in the path
            $path = dirname($path);
        }

        // Create a new array to store the reversed elements
        $reversedArray = array();

        // Iterate through the input array in reverse order
        for ($i = count($menuItems) - 1; $i >= 0; $i--) {
            // Get the current key-value pair
            $url = array_keys($menuItems)[$i];
            $value = $menuItems[$url];

            // Add the key-value pair to the new array
            $this->addBreadcrumbs([$url=>$value]);
            $reversedArray[$url] = $value;
        }

        return $reversedArray;
    }

    /**
    * The root path where the _crumb-en.menu files are located.
    */
    private $rootPath = '/sites/healthycanadians';
    public function setRootPath(string $path): self
    {
        $this->rootPath=$path;
        return $this;
    }

    protected function getRootPath(): string
    {
        return $this->rootPath;
    }

    private $lang='en';
    public function setLang(string $lang): self
    {
        $this->lang=$lang;
        return $this;
    }
    protected function getLang(): string
    {
        return $this->lang;
    }
}
