<?php

return [
    'service_manager' => [
        'services' => [
            // by default OAuth is disabled
            'OAuth2Enabled' => false,
            // Time To Live of the JWT, 86400 = 24 hours
            'OAuth2TTL' => 86400,
            // Time To Live of the refresh token, 31536000 = 1 year (more or less)
            'OAuth2RefreshTTL' => 31536000,
        ],
    ],
];
