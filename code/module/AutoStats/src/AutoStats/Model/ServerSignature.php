<?php

namespace AutoStats\Model;

use Void\ServerSpecs;

class ServerSignature
{
    public static function get(): array
    {
        $data = array_merge(
            ServerSpecs::get(),
            [
                'isDocker' => getenv('IN_DOCKER'),
                'containerName' => getenv('IN_DOCKER') ? getenv('DOCKER_CONTAINER_NAME') : '',
                'framework' => getenv('USING_FRAMEWORK') ?? 'Laminas',
            ]
        );

        if(getenv('DATABASE_SERVER')) {
            $data['databaseServer'] = getenv('DATABASE_SERVER');
            $data['databaseName'] = getenv('DATABASE_DBNAME');
        }
        return $data;
    }
}
