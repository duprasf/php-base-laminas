<?php
namespace TranslationExtractor\Model;

use DirectoryIterator;
use InvalidArgumentException;
use RuntimeException;
use LengthException;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RecursiveRegexIterator;


/**
* Class that extracts all the strings from a project (folder)
* and writes them in a .po file to be used by service like poeditor.com
*
* @author Francois Dupras francois.dupras@hc-sc.gc.ca
* @link poeditor.com
*/
class Extractor
{
    protected $source = null;
    protected $output = null;
    protected $array = array();

    /**
    * Set the source that will be read for extraction
    *
    * @param String $source
    * @return Extractor
    */
    public function setSource(String $source)
    {
        if(!is_string($source)) {
            return false;
        }
        if(strpos($source, '/') !== 0) {
            $source = __DIR__.'/'.$source;
        }
        $this->source = $source;
        return $this;
    }

    /**
    * Set the output file
    *
    * @param String $output
    * @return Extractor
    */
    public function setOutput(String $output)
    {
        if(!is_string($output)) {
            return false;
        }
        $this->output = $output;
        return $this;
    }

    /**
    * Extract all modules from a Laminas project (folders inside module/, vendor/ and apps/)
    *
    * @param String $source
    * @param String $output
    * @return Extractor
    */
    public function extractAllModules(String $source = null, String $output=null)
    {
        if(is_null($source)) {
            $source = $this->source;
        }
        $source = rtrim($source,'/');

        $outputDir = getcwd();
        if(!is_null($output) && is_dir($output)) {
            $outputDir = realpath($output);
        }

        if(is_null($source)) {
            $source = __DIR__;
        }

        foreach(array('module', 'vendor', 'apps') as $baseDir) {
            $dirs = new DirectoryIterator($source.DIRECTORY_SEPARATOR.$baseDir);
            foreach($dirs as $dir) {
                if($dir->isDot()) {
                    continue;
                }
                $module = $dir->getBasename();
                $this->processFolder($baseDir.DIRECTORY_SEPARATOR.$module, $outputDir.DIRECTORY_SEPARATOR.\Void\StringFunction::clean($module).'.po');
            }
        }

        return $this;
    }

    /**
    * Process a single folder
    *
    * @param String $source
    * @param String $output
    * @return Extractor
    */
    public function processFolder(String $source = null, String $output=null)
    {
        if(is_null($source)) {
            $source = $this->source;
        }
        if(is_null($output)) {
            $output = $this->output;
        }

        $array = $this->parseFolder($source);
        $this->writePo($output, $array);

        return $this;
    }

    /**
    * put your comment there...
    *
    * @param array $list
    * @param String $output if null, the default output
    * @throws InvalidArgumentException when the source is invalid
    * @throws LengthException When no translation strings are found
    * @throws Exception
    * @return Extractor
    */
    public function processFileList(array $list, String $output=null)
    {
        if(is_null($output)) {
            $output = $this->output;
        }

        $array = array();
        foreach($list as $file) {
            if(file_exists($file)) {
                $array = array_merge($array, $this->parseFolder($file));
            }
        }
        $this->writePo($output, $array);

        return $this;
    }

    /**
    * Write the .po file
    *
    * @param String $output
    * @param array $array the array of strings
    * @throws RuntimeException when output is not writable
    * @return Extractor
    */
    public function writePo(String $output, array $array)
    {
        // add the extension 'po' if the current filename does not have the right extension
        if(pathinfo($output, PATHINFO_EXTENSION) !== 'po') {
            $output.='.po';
        }

        if(!count($array)) {
            throw new LengthException();
        }

        if(!is_writable($output)) {
            throw new RuntimeException('Output is not writable');
        }

        file_put_contents($output, 'msgid ""'.PHP_EOL.'msgstr ""'.PHP_EOL.PHP_EOL);

        array_walk(
            $array,
            function(&$item, $key, $output) {
                $filepath = $item['file'];
                if(strpos($filepath, __DIR__) === 0) {
                    $filepath = substr($filepath, strlen(__DIR__)+1);
                }
                file_put_contents($output,
                    PHP_EOL.
                    '#: first found in '.$filepath.(isset($item['line']) ? ':'.$item['line'] : '').(isset($item['comment']) ? ' '.$item['comment'] : '').PHP_EOL.
                    'msgid "'.preg_replace('((?<!\\\\)")', '\"', $key).'"'.PHP_EOL.
                    'msgstr "'.preg_replace('((?<!\\\\)")', '\"', $item['msgstr']).'"'.PHP_EOL
                    , FILE_APPEND
                );
            },
            $output
        );

        return $this;
    }

    /**
    * Parse all files in a folder
    *
    * @param String $source a directory to scan
    */
    public function parseFolder(String $source)
    {
        $array = array();

        if(!file_exists($source) && !is_dir($source)) {
            throw new InvalidArgumentException('folder '.$source.' not found');
        }

        if(!is_dir($source)) {
            $this->parseFile($source, $array);
            return $array;
        }

        $directory = new RecursiveDirectoryIterator($source);
        $iterator = new RecursiveIteratorIterator($directory);
        $files = new RegexIterator($iterator, '/^.+\.ph(?:p|tml)$/i', RecursiveRegexIterator::GET_MATCH);
        foreach($files as $file) {
            $this->parseFile($file[0], $array);
        }
        return $array;
    }

    /**
    * Parse a single file
    *
    * @param String $file
    * @param array $array
    */
    public function parseFile(String $file, array &$array)
    {
        print "parsing '{$file}' ";
        $count = 0;
        $content = file_get_contents($file);
        $contentPerLine = null;

        if(basename($file) == 'module.config.php' || preg_match('(\.(?:local|global)\.php$)', basename($file))) {
            $config = include($file);
            if(isset($config['router']) && isset($config['router']['routes'])) {
                $entries = $this->getTranslationTermsFromRoutes($config['router']['routes']);
                foreach($entries as $key=>$entry) {
                    $entry['file'] = $file;
                    if(!isset($array[$entry['msgstr']])) {
                        $array[$entry['msgstr']] = $entry;
                        $count++;
                    }
                }
            }
        }

        preg_match_all('((?:_|->translate)\(\s*?("|\')((?:(?=(\\\?))\3.)*?)\1(?:\)|,))sm', $content, $out);
        $entries = array_filter($out[2]);
        foreach($entries as $entry) {
            if(!isset($array[$entry])) {
                $line = 0;
                if($contentPerLine == null) {
                    $contentPerLine = file($file);
                }
                foreach($contentPerLine as $key=>$content) {
                    if(strpos($content, $entry) !== false) {
                        $line = $key+1;
                        break;
                    }
                }
                $array[$entry] = array('file'=>$file, 'msgstr'=>$entry, 'line'=>$line);
                $count++;
            }
        }
        print "found {$count} strings".PHP_EOL;
    }

    /**
    * Extract the rtanslation terms from all routes in the config file
    *
    * @param array $routes
    * @return array
    */
    public function getTranslationTermsFromRoutes(array $routes)
    {
        $terms = array();
        foreach($routes as $key=>$route) {
            $terms = array_merge($terms, $this->getTranslationTermsFromRoute($route, $key));
        }
        return $terms;
    }

    /**
    * Parse a single route to find the translation strings
    *
    * @param array $route
    * @param mixed $key
    * @return array
    */
    public function getTranslationTermsFromRoute(array $route, $key)
    {
        $found = array();
        if(isset($route['options']) && isset($route['options']['route'])) {
            preg_match_all('({([a-zA-Z0-9_-]+)})',$route['options']['route'], $out);
            if(count($out[1])) {
                foreach($out[1] as $entry) {
                    $found[] = array('msgstr'=>$entry, 'comment'=>"(from route {$key})");
                }
            }
        }
        if(isset($route['child_routes'])) {
            foreach($route['child_routes'] as $childKey=>$child) {
                $found = array_merge($found, $this->getTranslationTermsFromRoute($child, $key.'/'.$childKey));
            }
        }

        return $found;
    }
}
