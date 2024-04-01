<?php
namespace AutoStats\Model;

class ServerSignature
{
    static public function get()
    {
        preg_match('("([^\(]*))', `cat /etc/*-release | grep PRETTY_NAME`, $out);
        $data = [
             'OS'=>trim($out[1]),
             'isDocker'=>getenv('IN_DOCKER'),
             'containerName'=>getenv('IN_DOCKER')?getenv('DOCKER_CONTAINER_NAME'):'',
             'phpVersion'=>PHP_VERSION,
             'apacheVersion'=>$_SERVER['SERVER_SOFTWARE'],
             'framework'=>getenv('USING_FRAMEWORK'),
        ];
        return $data;
    }
}
