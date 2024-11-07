<?php

namespace GcDirectory;

# LIVE
# GC Directory (read-only) | https://gcdirectory-gcannuaire.ssc-spc.gc.ca/gapi/v2/doc
# Admin (read/write)       | https://gcdirectory-gcannuaire.ssc-spc.gc.ca/gaapi/v2/doc

# TEST
# GC Direcotry (read-only) | https://geds20api-sage20api.itsso.gc.ca/gapi/v2/doc
# Admin (read/write)       | https://geds20api-sage20api.itsso.gc.ca/gaapi/v2/doc

return [
    'service_manager' => [
        'services' => [
            'gc-directory-config' => [
                "secret-token" => getenv("GCDIRECTORY_SECRET_TOKEN"),
                "base-url" => getenv('GCDIRECTORY_API_URL'),
                "username" => getenv('GCDIRECTORY_USER'),
                "password" => getenv('GCDIRECTORY_PASSWORD'),
                "adminKey" => getenv('GCDIRECTORY_ADMIN_KEY'),
                "deptId" => getenv('GCDIRECTORY_DEPT_ID'),
            ],
        ],
    ],
];
