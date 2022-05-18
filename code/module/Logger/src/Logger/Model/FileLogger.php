<?php
namespace Logger\Model;

use Psr\Log;
use Logger\Model\LoggerTrait;

class FileLogger implements Log\LoggerInterface
{
    use LoggerTrait;

    private function __destruct()
    {
        if($this->filehandle) {
            fclose($this->filehandle);
        }
    }

    private $filename;
    public function setFilename(String $filename)
    {
        if(is_dir($filename)) {
            $filename=realpath($filename.DIRECTORY_SEPARATOR.'logs');
        }
        if(!file_exists($filename)) {
            touch($filename);
            chmod($filename, 0664);
        }
        if(!is_writable($filename)) {
            throw new \Exception('Log file is not writable');
        }
        $this->filename = $filename;
        return $this;
    }
    public function getFilename()
    {
        return $this->filename;
    }

    private $filehandle;
    protected function getHandle()
    {
        if(!$this->filehandle) {
            $this->filehandle = fopen($this->getFilename(), 'a');
        }
        return $this->filehandle;
    }

    public function log($level, $message, array $context = array())
    {
        $f = $this->getHandle();
        fwrite($f, json_encode(['timestamp'=>time(), 'level'=>$level, 'message'=>$this->interpolate($message, $context)]));
    }
}
