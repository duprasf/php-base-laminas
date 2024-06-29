<?php

return [
    'service_manager' => [
        'services' => [
            'useAdobeAnalytics' => getenv('ANALYTICS_USE_ADOBE'),
            'useAdobeAnalyticsWithPersonalInformation' => getenv('ANALYTICS_USE_ADOBE_WITH_PERSONAL_INFORMATION'),
            'googleAnalyticsId' => getenv('ANALYTICS_GA_ID'),
        ],
    ],
];
