<?php

namespace AutoStats\Model;

use Void\ServerSpecs;

class ServerSignature
{
    public static function get(int|array $params=0): array
    {
        $data = array_merge(
            ServerSpecs::get($params),
            [
                'isDocker' => getExistingEnv('IN_DOCKER'),
                'containerName' => getExistingEnv('IN_DOCKER') ? getExistingEnv('DOCKER_CONTAINER_NAME') : '',
                'framework' => getExistingEnv('USING_FRAMEWORK') ?? 'Laminas',
            ]
        );

        if(getExistingEnv('DATABASE_SERVER')) {
            $data['databaseServer'] = getExistingEnv('DATABASE_SERVER');
            $data['databaseName'] = getExistingEnv('DATABASE_DBNAME');
        }
        return $data;
    }
}
