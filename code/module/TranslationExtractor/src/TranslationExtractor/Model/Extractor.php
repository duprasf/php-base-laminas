<?php
namespace TranslationExtractor\Model;

class Extractor
{
    protected $source = null;
    protected $output = null;
    protected $array = array();

    public function setSource($source)
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

    public function setOutput($output)
    {
        if(!is_string($output)) {
            return false;
        }
        $this->output = $output;
        return $this;
    }

    public function extractAllModules($source = null, $output=null)
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
            $dirs = new \DirectoryIterator($source.DIRECTORY_SEPARATOR.$baseDir);
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

    public function processFolder($source=null, $output=null)
    {
        if(is_null($source)) {
            $source = $this->source;
        }
        if(is_null($output)) {
            $output = $this->output;
        }

        try {
            $array = $this->parseFolder($source);
            $this->writePo($output, $array);
        }
        catch(\InvalidArgumentException $e) {
            print "Source is invalid".PHP_EOL;
        }
        catch(\LengthException $e) {
            print "No translated string found".PHP_EOL;
        }
        catch(\Exception $e) {
            print $e->getMessage().PHP_EOL;
        }

        return $this;
    }

    public function processFileList(array $list, $output=null)
    {
        if(is_null($output)) {
            $output = $this->output;
        }

        try {
            $array = array();
            foreach($list as $file) {
                if(file_exists($file)) {
                    $array = array_merge($array, $this->parseFolder($file));
                }
            }
            $this->writePo($output, $array);
        }
        catch(\InvalidArgumentException $e) {
            print "Source is invalid".PHP_EOL;
        }
        catch(\LengthException $e) {
            print "No translated string found".PHP_EOL;
        }
        catch(\Exception $e) {
            print $e->getMessage().PHP_EOL;
        }

        return $this;
    }

    public function writePo($output, array $array)
    {
        if(pathinfo($output, PATHINFO_EXTENSION) !== 'po') {
            $output.='.po';
        }

        if(count($array)) {
            file_put_contents($output, 'msgid ""'.PHP_EOL.'msgstr ""'.PHP_EOL.PHP_EOL);

            array_walk($array, function(&$item, $key, $output) {
                $filepath = $item['file'];
                if(strpos($filepath, __DIR__) === 0) {
                    $filepath = substr($filepath, strlen(__DIR__)+1);
                }
                file_put_contents($output,
                    PHP_EOL.
                    '#: first found in '.$filepath.(isset($item['line']) ? ':'.$item['line'] : '').(isset($item['comment']) ? ' '.$item['comment'] : '').PHP_EOL.
                    'msgid "'.preg_replace('((?<!\\\\)")', '\"', $key).'"'.PHP_EOL.
                    'msgstr "'.preg_replace('((?<!\\\\)")', '\"', $item['msgstr']).'"'.PHP_EOL
                    , FILE_APPEND);
                }, $output );
        }
        else {
            throw new \LengthException();
        }
    }

    public function parseFolder($source)
    {
        $array = array();

        if(is_dir($source) || file_exists($source)) {
            if(is_dir($source)) {
                $directory = new \RecursiveDirectoryIterator($source);
                $iterator = new \RecursiveIteratorIterator($directory);
                $files = new \RegexIterator($iterator, '/^.+\.ph(?:p|tml)$/i', \RecursiveRegexIterator::GET_MATCH);
                foreach($files as $file) {
                    $this->parseFile($file[0], $array);
                }
            }
            else {
                $this->parseFile($source, $array);
            }
        }
        else {
            throw new \InvalidArgumentException('folder not found');
        }
        return $array;
    }

    public function parseFile($file, &$array)
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

    public function getTranslationTermsFromRoutes(array $routes)
    {
        $terms = array();
        foreach($routes as $key=>$route) {
            $terms = array_merge($terms, $this->getTranslationTermsFromRoute($route, $key));
        }
        return $terms;
    }

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
