<?php

/**
 * List of enabled modules for this application.
 *
 * This should be an array of module namespaces used in the application.
 */
$appsModules = file_exists(__DIR__.'/autoload/_modules.local.php')
    ? include(__DIR__.'/autoload/_modules.local.php')
    : array()
;

return array_merge([
    'Laminas\Mvc\Plugin\FilePrg',
    'Laminas\Mvc\Plugin\FlashMessenger',
    'Laminas\Mvc\Plugin\Identity',
    'Laminas\Mvc\Plugin\Prg',
    'Laminas\Session',
    'Laminas\Mvc\I18n',
    'Laminas\I18n',
    'Laminas\Log',
    'Laminas\Form',
    'Laminas\Hydrator',
    'Laminas\InputFilter',
    'Laminas\Filter',
    'Laminas\Di',
    'Laminas\Router',
    'Laminas\Validator',
    //'LmcUser',
    'UserAuth',
    'Application',
    'PublicAsset',
],$appsModules);
