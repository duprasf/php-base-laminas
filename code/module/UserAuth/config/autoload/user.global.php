<?php

namespace UserAuth;

return [
    'service_manager' => [
        'service' => [
            // default is 36 hours (129600)
            'UserAuthSessionLength' => getExistingEnv('LAMINAS_USER_SESSION_LENGTH') ?? 129600,
        ],
    ],
];
